"""Apply/down/up existing migrations only in the explicitly named P1 isolated database."""
from __future__ import annotations

import json
import subprocess
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path("/home/soto/Code/more")
OUT = ROOT / "storage/app/ai-p1"
TARGET = "p1_migration_testing"
HOST = "more-ai-p1-pg-testing-20261004"
APP = "more-laravel.test-1"


def main() -> None:
    report_path = OUT / "isolated-up-down-up.json"
    if report_path.exists():
        raise SystemExit("P1_REPORT_EXISTS_USE_NEW_DATABASE_AND_REPORT")
    settings = json.loads((OUT / "testing-db.private.json").read_text())
    if settings["host"] != HOST or settings["database"] != "testing":
        raise SystemExit("P1_ISOLATED_CONFIG_ASSERTION_FAILED")
    password = settings["password"]
    container = json.loads(subprocess.run(["docker", "inspect", HOST], text=True, capture_output=True, check=True).stdout)[0]
    mounts = container["Mounts"]
    if any(mount.get("Name") == "more_sail-pgsql" or "more_sail-pgsql" in mount.get("Source", "") for mount in mounts):
        raise SystemExit("P1_COMMERCIAL_VOLUME_MOUNTED_ABORT")
    stages = []

    def run(command: list[str], stage: str) -> subprocess.CompletedProcess[str]:
        result = subprocess.run(command, cwd=ROOT, text=True, capture_output=True, check=False)
        output = result.stdout.replace(password, "[REDACTED]")
        error = result.stderr.replace(password, "[REDACTED]")
        (OUT / f"isolated-migration-{stage}.log").write_text(output + error, encoding="utf-8")
        if result.returncode:
            raise RuntimeError(f"P1_STAGE_FAILED:{stage}:exit={result.returncode}")
        return result

    environment = {
        "APP_ENV": "testing", "DB_CONNECTION": "pgsql", "DB_URL": "", "DB_HOST": HOST,
        "DB_PORT": "5432", "DB_DATABASE": TARGET, "DB_USERNAME": settings["username"],
        "DB_PASSWORD": password,
    }
    base = ["docker", "exec"]
    for key, value in environment.items():
        base += ["-e", f"{key}={value}"]
    base += [APP]
    bootstrap = (
        'require "/var/www/html/services/mati-ai/scripts/p1/bootstrap.php";'
        '$c=Illuminate\\Support\\Facades\\DB::connection();'
        '$r=$c->selectOne("SELECT current_database() AS db,current_setting(\'server_version\') AS version");'
        'if(app()->environment()!=="testing"||$c->getDriverName()!=="pgsql"'
        f'||$r->db!=="{TARGET}"||$c->getConfig("host")!=="{HOST}"'
        '||!str_starts_with($r->version,"18.")){throw new RuntimeException("P1_ISOLATION_ASSERTION_FAILED");}'
    )

    def assert_live(stage: str) -> None:
        run(base + ["php", "-r", bootstrap + 'echo json_encode(["database"=>$r->db,"server_version"=>$r->version,"environment"=>app()->environment()]);'], stage + "-guard")

    def inspect(stage: str) -> dict:
        sql = (
            "SELECT json_build_object('database',current_database(),'server_version',current_setting('server_version'),"
            "'pgvector_version',(SELECT extversion FROM pg_extension WHERE extname='vector'),"
            "'ai_tables',(SELECT count(*) FROM information_schema.tables WHERE table_schema='public' AND table_name IN ('ai_model_versions','ai_runs','ai_suggestions','ai_human_reviews','ai_product_embeddings')),"
            "'modelo_pre',(SELECT count(*) FROM information_schema.columns WHERE table_schema='public' AND table_name='prendas' AND column_name='modelo_pre'),"
            "'products',(SELECT count(*) FROM prendas),"
            "'vector_type',(SELECT format_type(a.atttypid,a.atttypmod) FROM pg_attribute a JOIN pg_class c ON c.oid=a.attrelid WHERE c.relname='ai_product_embeddings' AND a.attname='embedding' AND NOT a.attisdropped),"
            "'ai_foreign_keys',(SELECT count(*) FROM pg_constraint c JOIN pg_class t ON t.oid=c.conrelid WHERE t.relname IN ('ai_model_versions','ai_runs','ai_suggestions','ai_human_reviews','ai_product_embeddings') AND c.contype='f'))::text AS evidence"
        )
        code = bootstrap + 'echo Illuminate\\Support\\Facades\\DB::selectOne(' + json.dumps(sql) + ')->evidence;'
        evidence = json.loads(run(base + ["php", "-r", code], stage + "-schema").stdout)
        stages.append({"stage": stage, **evidence})
        return evidence

    created = run(
        ["docker", "exec", HOST, "createdb", "-U", settings["username"], TARGET],
        "createdb",
    )
    if created.returncode != 0:
        raise RuntimeError("P1_ISOLATED_DATABASE_NOT_CREATED")
    assert_live("first-up")
    run(base + ["php", "artisan", "migrate", "--database=pgsql", "--force", "--no-ansi"], "first-up")
    first = inspect("first-up")
    assert_live("down")
    run(base + ["php", "artisan", "migrate:rollback", "--database=pgsql", "--step=2", "--force", "--no-ansi"], "down")
    down = inspect("down")
    assert_live("second-up")
    run(base + ["php", "artisan", "migrate", "--database=pgsql", "--force", "--no-ansi"], "second-up")
    second = inspect("second-up")
    checks = {
        "both_up_states_five_tables": first["ai_tables"] == second["ai_tables"] == 5,
        "both_up_states_modelo_pre_present": first["modelo_pre"] == second["modelo_pre"] == 1,
        "both_up_states_vector_unbounded": first["vector_type"] == second["vector_type"] == "vector",
        "both_up_states_11_foreign_keys": first["ai_foreign_keys"] == second["ai_foreign_keys"] == 11,
        "down_removed_only_ai_tables_and_modelo_pre": down["ai_tables"] == 0 and down["modelo_pre"] == 0,
        "extension_retained_through_down": first["pgvector_version"] == down["pgvector_version"] == second["pgvector_version"] == "0.8.7",
        "isolated_catalog_remained_empty": first["products"] == down["products"] == second["products"] == 0,
        "target_database_every_stage": all(stage["database"] == TARGET for stage in stages),
    }
    report = {
        "utc": datetime.now(timezone.utc).isoformat(), "host": HOST, "database": TARGET,
        "commercial_volume_mounted": False, "isolated_container_mounts": mounts, "database_testing_frontend_touched": False,
        "migration_source_modified": False, "stages": stages, "checks": checks,
        "passed": all(checks.values()),
    }
    report_path.write_text(json.dumps(report, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(report, indent=2))
    raise SystemExit(0 if report["passed"] else 1)


if __name__ == "__main__":
    main()
