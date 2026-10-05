"""Validate the authorized local stack; secrets stay in memory, outputs are P1-only."""
import hashlib,json,time
from pathlib import Path
import httpx,numpy as np
ROOT=Path('/home/soto/Code/more');OUT=ROOT/'storage/app/ai-p1'
env=dict(l.split('=',1) for l in (ROOT/'.env').read_text().splitlines() if l and not l.startswith('#') and '=' in l)
client=httpx.Client(base_url='http://127.0.0.1:8001',timeout=90)
report={'token_present':bool(env.get('MATI_AI_TOKEN')),'token_never_recorded':True,'checks':{}}
for name,headers in [('missing',{}),('incorrect',{'Authorization':'Bearer p1-intentionally-incorrect'}),('correct',{'Authorization':'Bearer '+env['MATI_AI_TOKEN']})]:
 r=client.get('/api/v1/capabilities',headers=headers);report['checks']['auth_'+name]={'http_status':r.status_code,'body':r.json()}
r=client.get('/health');report['health']={'http_status':r.status_code,'body':r.json(),'liveness_only':True}
client.headers['Authorization']='Bearer '+env['MATI_AI_TOKEN']
m=json.loads((ROOT/'storage/app/ai-audit/evaluation_manifest.json').read_text());row=next(x for x in m['images'] if x['image_id']==13);p=Path(row['local_path'])
for name,path,source,data in [('embedding','/api/v1/garments/embedding',p,{}),('image13','/api/v1/garments/analyze',p,{'categories':json.dumps(m['taxonomies']['categories']),'colors':json.dumps(m['taxonomies']['colors']),'patterns':json.dumps(m['taxonomies']['patterns']),'include_embedding':'true'}),('ocr_synthetic','/api/v1/identification/label',OUT/'label-synthetic.png',{'brands':json.dumps(m['taxonomies']['brands']),'sizes':json.dumps(m['taxonomies']['sizes'])}),('barcode','/api/v1/identification/barcode',OUT/'barcode-ean13.png',{}),('qr','/api/v1/identification/barcode',OUT/'qr-url.png',{})]:
 t=time.perf_counter();r=client.post(path,files={'image':(source.name,source.read_bytes(),'image/png' if source.suffix=='.png' else row['mime'])},data=data);report[name]={'http_status':r.status_code,'wall_ms':(time.perf_counter()-t)*1000,'body':r.json()}
 (OUT/(name+'-http.json')).write_text(json.dumps(report[name],indent=2))
 e=report[name]['body'].get('data',{}).get('embedding')
 if e:
  report[name]['embedding_check']={'dimension':len(e['values']),'l2_norm':float(np.linalg.norm(e['values'])),'finite':bool(np.isfinite(e['values']).all()),'revision':e['model_revision']}
  # Keep complete vector in dedicated raw evidence, concise runtime report carries identity only.
  report[name]['body']['data']['embedding']['values']='[see dedicated raw evidence]'
report['capabilities_after_load']=client.get('/api/v1/capabilities').json()
report['passed']=(report['checks']['auth_missing']['http_status']==401 and report['checks']['auth_incorrect']['http_status']==401 and report['checks']['auth_correct']['http_status']==200 and all(report[n]['http_status']==200 for n in ['embedding','image13','ocr_synthetic','barcode','qr']) and report['embedding']['embedding_check']['dimension']==768 and bool(report['ocr_synthetic']['body'].get('data',{}).get('raw_text')))
(OUT/'runtime-validation.json').write_text(json.dumps(report,indent=2))
print(json.dumps({'passed':report['passed'],'auth':{k:v['http_status'] for k,v in report['checks'].items()},'health':report['health'],'operations':{n:{'http_status':report[n]['http_status'],'status':report[n]['body'].get('status'),'wall_ms':report[n]['wall_ms']} for n in ['embedding','image13','ocr_synthetic','barcode','qr']},'embedding':report['embedding'].get('embedding_check'),'ocr_keys':list(report['ocr_synthetic']['body'].get('data',{}))},indent=2))
