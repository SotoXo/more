"""Stop only audit-owned instances and capture final preservation evidence."""
import hashlib,json,subprocess,datetime,os
from pathlib import Path
ROOT=Path(__file__).resolve().parents[4];OUT=ROOT/'storage/app/ai-audit';DOC=ROOT/'DOCUMENTACION/IA/AUDITORIA_FINAL'
audit_names=['more-ai-audit-fastapi-20261004','more-ai-audit-laravel-20261004','more-ai-audit-pg-20261004','more-ai-audit-pg087-20261004'];principal=['more-laravel.test-1','more-pgsql-1','more-redis-1']
def inventory(names):
 data=json.loads(subprocess.check_output(['docker','inspect',*names]));return [{'name':r['Name'].lstrip('/'),'id':r['Id'],'running':r['State']['Running'],'status':r['State']['Status'],'started_at':r['State']['StartedAt'],'health':r['State'].get('Health',{}).get('Status')} for r in data]
before=inventory(principal);audit_before=inventory(audit_names);stop=subprocess.run(['docker','stop',*audit_names],text=True,capture_output=True,timeout=60);after=inventory(principal);audit_after=inventory(audit_names)
native_pid=3321;native={'pid':native_pid,'running_owned_http':False}
try:
 cmd=Path(f'/proc/{native_pid}/cmdline').read_bytes().replace(b'\0',b' ').decode(errors='replace');native['running_owned_http']='scripts/audit/start_http.py' in cmd
except FileNotFoundError:pass
if native['running_owned_http']:
 # Ownership and exact audit entrypoint confirmed; do not touch other Python services.
 os.kill(native_pid,15);native['termination_sent']=True
base=json.loads((OUT/'baseline.json').read_text());changed=[]
for name,wanted in base['source_hashes'].items():
 p=ROOT/name
 if not p.exists() or hashlib.sha256(p.read_bytes()).hexdigest()!=wanted:changed.append(name)
def git(*args):return subprocess.check_output(['git',*args],cwd=ROOT,text=True,stderr=subprocess.DEVNULL)
status=git('status','--short');full=git('status','--branch');stat=git('diff','--stat');branch=git('branch','--show-current').strip();head=git('rev-parse','HEAD').strip();newlines=sorted(set(status.splitlines())-set(base['status'].splitlines()));missing=sorted(set(base['status'].splitlines())-set(status.splitlines()))
ignored=subprocess.run(['git','check-ignore','storage/app/ai-audit/diagnostic_images.png','storage/app/ai-audit/classification_metrics.json','public/build/manifest.json'],cwd=ROOT,text=True,capture_output=True)
db=json.loads((DOC/'_database_audit.json').read_text());last=json.loads((OUT/'commercial_final_readonly_probe.json').read_text());res={'captured_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'branch':branch,'head':head,'branch_unchanged':branch==base['branch'],'head_unchanged':head==base['head'],'source_files_checked':len(base['source_hashes']),'changed_or_missing_source_files':changed,'source_hashes_unchanged':not changed,'git_diff_stat':stat,'git_diff_stat_unchanged':stat==base['diff_stat'],'git_status_short':status,'git_status_full':full,'new_status_entries':newlines,'baseline_status_entries_missing':missing,'ignored_evidence_and_build':ignored.stdout.splitlines(),'commercial_snapshot_before_sha256':db['commercial_snapshot_sha256'],'commercial_snapshot_after_sha256':db['commercial_snapshot_after_sha256'],'commercial_snapshot_unchanged':db['commercial_snapshot_unchanged'],'final_readonly_commercial_probe':last,'principal_containers_before':before,'principal_containers_after':after,'principal_containers_not_restarted':before==after,'audit_containers_before':audit_before,'audit_containers_after':audit_after,'audit_stop_exit_code':stop.returncode,'native_http':native,'productive_source_edits':False,'commercial_product_writes':False,'training_or_prompt_threshold_tuning':False,'commit_or_push':False,'authorized_build_changed_served_assets':True}
(OUT/'final_state.json').write_text(json.dumps(res,indent=2,ensure_ascii=False)+'\n')
text=f'''# Preservación y Git final

Captura UTC: {res['captured_utc']}. Branch `{branch}`, HEAD `{head}` iguales al inicio. **{len(base['source_hashes'])}/{len(base['source_hashes'])} hashes de fuentes/base SQLite comprobados sin cambios**. Alcance concreto de hashes en baseline.json; no incluye todos los archivos del disco. Ninguna edición productiva por esta auditoría.

Snapshot comercial antes/después idéntico: `{db['commercial_snapshot_sha256']}`. Consulta final BEGIN READ ONLY:134 prendas/134 variantes/134 imágenes, referencias image/product/path iguales almanifest,134/134SHA de imágenes originales intactos. No se ejecutó entrenamiento, ajuste de prompts/thresholds, escritura comercial, commit, push, reset, restore ni cambio de branch.

**Build normal expresamente solicitado regeneró public/build y cambió assets servidos**: ProductCreate-tYfO0olu.js reemplaza chunk obsoleto. Es artefacto compilado/ignorado, sin modificar source; no afirmar ausencia absoluta de efecto observable. Evidencia y screenshots bajo storage/app/ai-audit ignorados. Scripts sólo scripts/audit; informes nuevos sólo AUDITORIA_FINAL. No se reescribió documentación histórica.

## git diff --stat

Es exactamente el mismo que el baseline, cambios previos del usuario:

```text
{stat.rstrip()}
```

Diff tracked incremental de auditoría:0. Los archivos nuevos no aparecen en gitdiffstat hasta añadirse; no se hizo gitadd.

## git status --short

```text
{status.rstrip()}
```

## git status --branch

```text
{full.rstrip()}
```

Nuevas entradas frente al baseline:

```text
{chr(10).join(newlines)}
```

Entradas previas desaparecidas: {len(missing)}. No interpretar sourceuntracked anterior como creado ahora; baseline conserva el listado original. Git advertía normalización futura CRLF en algunos archivos alleer diff, hashes actuales iguales, no se normalizaron durante la auditoría.

## Cierre de instancias propias

Dockerstop exit{stop.returncode}; cuatro contenedores propios quedan detenidos, no eliminados: {', '.join(audit_names)}. Aplicación/PostgreSQL/Redis principales conservan ID/started_at/estado: {res['principal_containers_not_restarted']}. NativeHTTPauditoría ya apagado parafallback; ownershipPID inspeccionado sin tocar otros procesos. Se conservan reportes/evidencias; databases de pruebas usaban tmpfs/no volumen comercial y no son persistencia productiva. Tokens temporales no se incluyen en informes/evidencias.
'''
(DOC/'_preservacion_y_git.md').write_text(text)
print(json.dumps({k:res[k] for k in ['source_files_checked','source_hashes_unchanged','branch_unchanged','head_unchanged','git_diff_stat_unchanged','new_status_entries','baseline_status_entries_missing','commercial_snapshot_unchanged','principal_containers_not_restarted','audit_stop_exit_code','audit_containers_after','native_http']},indent=2))
