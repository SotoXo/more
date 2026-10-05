"""Run the authorized eight-image normal CLI sample, then exact SQL/NumPy and idempotence."""
from __future__ import annotations

import json
import re
import subprocess
from pathlib import Path

ROOT = Path("/home/soto/Code/more")
OUT = ROOT / "storage/app/ai-p1"
PHP = [
    "docker", "exec", "-e", "P1_EXPECT_DATABASE=laravel", "-e", "P1_EXPECT_HOST=pgsql",
    "more-laravel.test-1", "php",
]


def main() -> None:
    report_path = OUT / "sample-validation.json"
    if report_path.exists() or (OUT / "pgvector-sample.json").exists():
        raise SystemExit("P1_SAMPLE_REPORT_EXISTS_ABORT")
    before_path = OUT / "post-migration.json"
    before = json.loads(before_path.read_text())
    if before["connection"]["database"] != "laravel" or any(before["ai_counts"].values()):
        raise SystemExit("P1_REQUIRES_POST_MIGRATION_EMPTY_AI_BASELINE")
    logs = []

    def run(command: list[str], log_name: str) -> str:
        result = subprocess.run(command, cwd=ROOT, text=True, capture_output=True, check=False)
        log = OUT / log_name
        log.write_text(result.stdout + result.stderr, encoding="utf-8")
        logs.append(str(log.relative_to(ROOT)))
        if result.returncode:
            raise RuntimeError(f"P1_COMMAND_FAILED:{log_name}:exit={result.returncode}")
        return result.stdout

    # Recheck current runtime identity, schema and catalogue before the first write.
    run(PHP + ["services/mati-ai/scripts/p1/guard_catalog.php", "--name=pre-sample"], "sample-pre-guard.log")
    current = json.loads((OUT / "pre-sample.json").read_text())
    if any(current["ai_counts"].values()):
        raise SystemExit("P1_AI_INDEX_ALREADY_POPULATED_ABORT")
    first_stdout = run(PHP + ["artisan", "mati-ai:index-catalog", "--limit=8", "--batch-size=1", "--no-ansi"], "sample-index-first.log")
    run(PHP + ["services/mati-ai/scripts/p1/capture_sample.php", "--name=pgvector-sample", "--max-sample=8"], "sample-capture.log")
    run(
        [
            str(ROOT / "services/mati-ai/.venv/bin/python"),
            "services/mati-ai/scripts/p1/compare_pgvector_numpy.py",
            "--input", str(OUT / "pgvector-sample.json"),
            "--output", str(OUT / "sample-numpy-agreement.json"),
        ],
        "sample-numpy.log",
    )
    run(PHP + ["services/mati-ai/scripts/p1/guard_catalog.php", "--name=post-sample", "--compare=pre-sample"], "sample-post-guard.log")
    second_stdout = run(PHP + ["artisan", "mati-ai:index-catalog", "--limit=8", "--batch-size=1", "--no-ansi"], "sample-index-repeat.log")
    run(PHP + ["services/mati-ai/scripts/p1/guard_catalog.php", "--name=post-sample-repeat", "--compare=post-sample", "--expect-idempotence"], "sample-repeat-guard.log")
    after = json.loads((OUT / "post-sample.json").read_text())
    repeated = json.loads((OUT / "post-sample-repeat.json").read_text())
    numpy = json.loads((OUT / "sample-numpy-agreement.json").read_text())
    count_pattern = r"Indexadas o actualizadas: (\d+)\. Ya indexadas: (\d+)\. No disponibles: (\d+)\."
    first = re.search(count_pattern, first_stdout)
    second = re.search(count_pattern, second_stdout)
    if not first or not second:
        raise RuntimeError("P1_COMMAND_COUNTERS_NOT_FOUND")
    first_counts = tuple(int(value) for value in first.groups())
    second_counts = tuple(int(value) for value in second.groups())
    processed = max([int(value) for value in re.findall(r"Procesadas: (\d+)\.", first_stdout)], default=0)
    repeated_processed = max([int(value) for value in re.findall(r"Procesadas: (\d+)\.", second_stdout)], default=0)
    checks = {
        "eight_real_images_processed": processed == repeated_processed == 8,
        "eight_indexed_first_pass": first_counts == (8, 0, 0),
        "eight_skipped_second_pass": second_counts == (0, 8, 0),
        "numpy_exact_search_passed": numpy["passed"],
        "commercial_fingerprints_and_image_sha_unchanged": after["comparison"]["commercial_fingerprints_identical"] and after["comparison"]["image_sha256_identical"] and repeated["comparison"]["commercial_fingerprints_identical"] and repeated["comparison"]["image_sha256_identical"],
        "canonical_rows_and_ai_counts_idempotent": repeated["comparison"]["canonical_identities_and_values_identical"] and repeated["comparison"]["ai_counts_identical"],
    }
    report = {
        "sample_n": 8,
        "persistence_route": "Existing normal mati-ai:index-catalog image mode, no force/from-runs",
        "first_pass": {"processed": processed, "indexed": first_counts[0], "updated": 0, "skipped": first_counts[1], "failed": first_counts[2]},
        "second_pass": {"processed": repeated_processed, "indexed": 0, "updated": 0, "skipped": second_counts[1], "failed": second_counts[2]},
        "eligible_products": after["eligible_products"], "covered_products": after["covered_products"],
        "numpy_report": "storage/app/ai-p1/sample-numpy-agreement.json",
        "checks": checks, "passed": all(checks.values()), "logs": logs,
    }
    report_path.write_text(json.dumps(report, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(report, indent=2))
    raise SystemExit(0 if report["passed"] else 1)


if __name__ == "__main__":
    main()
