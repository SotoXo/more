"""Audit-only aggregation of frozen evidence. No source/data mutations."""
import ast,json
from pathlib import Path
import numpy as np
ROOT=Path(__file__).resolve().parents[4];OUT=ROOT/'storage/app/ai-audit';DOC=ROOT/'DOCUMENTACION/IA/AUDITORIA_FINAL'
pred=json.loads((OUT/'classification_predictions.json').read_text());timing={}
for k in ['validation','quality','color','barcode','inference','total']:
 vals=[r['response']['timing_ms'][k] for r in pred if k in r['response']['timing_ms']]
 timing[k]={'n':len(vals),'min':float(np.min(vals)),'mean':float(np.mean(vals)),'p50':float(np.median(vals)),'p95':float(np.percentile(vals,95)),'max':float(np.max(vals))}
(OUT/'full_pipeline_timings.json').write_text(json.dumps({'scope':'native_HTTP130_actual_images_model_already_loaded','model_load_not_summed':'manager lifetime telemetry repeats earlier load, not cost per request','modules':timing},indent=2)+'\n')
errors=[]
for f in (ROOT/'services/mati-ai/src').rglob('*.py'):
 for n in ast.walk(ast.parse(f.read_text())):
  if isinstance(n,ast.Call) and isinstance(n.func,ast.Name) and n.func.id in ['PipelineError','FetchError'] and n.args and isinstance(n.args[0],ast.Constant) and isinstance(n.args[0].value,str):
   code=n.args[0].value;status=422
   if len(n.args)>2 and isinstance(n.args[2],ast.Constant):status=n.args[2].value
   for kw in n.keywords:
    if kw.arg=='status_code' and isinstance(kw.value,ast.Constant):status=kw.value.value
   errors.append({'code':code,'status_declared_or_default':status,'file':str(f.relative_to(ROOT)),'line':n.lineno,'evidence_kind':'source_not_all_runtime_verified'})
(OUT/'error_catalog_static.json').write_text(json.dumps(errors,indent=2)+'\n')
keys=['MATI_AI_ENABLED','MATI_AI_URL','MATI_AI_TOKEN','MATI_AI_TIMEOUT','MATI_AI_CONNECT_TIMEOUT','MATI_AI_VECTOR_ENABLED','MATI_AI_DUPLICATE_SIMILARITY','MATI_AI_MODEL_PROVIDER','MATI_AI_VISION_MODEL','MATI_AI_MODEL_REVISION','MATI_AI_DEVICE','MATI_AI_LOCAL_FILES_ONLY','MATI_AI_OCR_ENABLED','MATI_AI_OCR_DOWNLOAD_ENABLED','MATI_AI_OCR_MODEL_DIRECTORY','MATI_AI_EXTERNAL_SOURCES_FILE'];result={}
for p in [ROOT/'.env',ROOT/'services/mati-ai/.env']:
 values={}
 if p.exists():
  for line in p.read_text().splitlines():
   if '=' in line and not line.strip().startswith('#'):
    k,v=line.split('=',1);values[k.strip()]=v.strip().strip('"').strip("'")
 result[str(p.relative_to(ROOT))]={'file_present':p.exists(),'variables':{k:'absent' if k not in values else 'empty' if not values[k] else 'configured_value_not_logged' for k in keys}}
(OUT/'configuration_presence.json').write_text(json.dumps(result,indent=2)+'\n')
print('TIMINGS',timing)
print('ERRORS',sorted({r['code'] for r in errors}))
