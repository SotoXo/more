"""Real HTTP operation/negative/concurrency audit; ephemeral process only."""
import json,time,statistics,concurrent.futures,subprocess,hashlib
from pathlib import Path
import httpx
ROOT=Path('/home/soto/Code/more');OUT=ROOT/'storage/app/ai-audit';s=json.loads(Path('/tmp/mati-audit-http-secret.json').read_text());h={'Authorization':'Bearer '+s['token']};client=httpx.Client(base_url='http://127.0.0.1:8001',headers=h,timeout=90)
m=json.loads((OUT/'evaluation_manifest.json').read_text());row=m['images'][30];p=Path(row['local_path']);files={'image':(p.name,p.read_bytes(),row['mime'])};results=[]
def req(name,path,**kw):
 t=time.perf_counter();r=client.post(path,**kw);b=r.json();entry={'name':name,'path':path,'http_status':r.status_code,'wall_ms':(time.perf_counter()-t)*1000,'body':b};results.append(entry);return b
req('embedding_real','/api/v1/garments/embedding',files=files)
req('barcode_real_no_ground_truth','/api/v1/identification/barcode',files=files)
req('label_real_no_ground_truth','/api/v1/identification/label',files=files,data={'brands':json.dumps(m['taxonomies']['brands']),'sizes':json.dumps(m['taxonomies']['sizes'])})
req('match_synthetic_exact_sku','/api/v1/products/match',json={'query':{'sku':'AUDIT-SKU'},'candidates':[{'candidate_id':'audit','sku':'AUDIT-SKU'}],'limit':3})
req('external_search_no_active_source','/api/v1/products/external-search',json={'query':'blue shirt','brand':'ZARA'})
req('external_image_unknown_source','/api/v1/products/external-image',json={'source_id':'audit-nonexistent','product_url':'https://example.com/product','original_url':'https://example.com/image.jpg','purpose':'reference'})
for rights in ['unknown','prohibited','owned','licensed','provider_authorized','public_domain']:
 b=req('publication_'+rights,'/api/v1/images/prepare-publication',files=files,data={'approved':'true','rights_status':rights,'approval_id':'audit-human-approval-fixture'})
 # Base64 bytes are useful only to the consumer; do not duplicate them in result reports.
 if b.get('data'):
  for variant in b['data'].get('variants',[]):
   for k in list(variant):
    if 'base64' in k:variant[k]='[bytes omitted from summarized evidence]'
preds=json.loads((OUT/'classification_predictions.json').read_text());colors=[r['response']['data']['colorimetry'] for r in preds if r['response']['data']['colorimetry']['status']=='completed']
if len(colors)>1:req('compatibility_two_real_palettes','/api/v1/garments/compatibility',json={'garment_a':colors[0],'garment_b':colors[1]})
req('image_over_limit','/api/v1/garments/embedding',files={'image':('oversized.jpg',b'0'*(10*1024*1024+1),'image/jpeg')})
req('request_over_body_limit','/api/v1/products/match',headers={**h,'Content-Type':'application/json'},content=b'0'*(11*1024*1024+1))
req('invalid_taxonomy_duplicate','/api/v1/garments/analyze',files=files,data={'categories':json.dumps([{'id':1,'name':'CAMISA'},{'id':1,'name':'BLUSA'}])})
lat=[];mem=[];emb=[]
for i in range(20):
 b=req('warm_embedding_repeat_'+str(i),'/api/v1/garments/embedding',files=files);lat.append(results[-1]['wall_ms']);emb.append(b.get('data',{}).get('embedding',{}).get('values'))
 rss=next((line for line in Path('/proc/'+str(s['pid'])+'/status').read_text().splitlines() if line.startswith('VmRSS:')),None)
 mem.append({'iteration':i,'rss':rss,'vram_peak_model_mb':b.get('data',{}).get('inference',{}).get('vram_peak_mb')})
def one(i):
 t=time.perf_counter();r=httpx.post('http://127.0.0.1:8001/api/v1/garments/embedding',headers=h,files=files,timeout=90);b=r.json();return {'i':i,'http_status':r.status_code,'wall_ms':(time.perf_counter()-t)*1000,'error':b.get('error'),'status':b.get('status')}
with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:conc=list(pool.map(one,range(8)))
summary={'operation_results':results,'real_http':True,'temporary_config':True,'warm_embedding':{'n':20,'min':min(lat),'mean':statistics.mean(lat),'median':statistics.median(lat),'p95':sorted(lat)[18]+.05*(sorted(lat)[19]-sorted(lat)[18]),'max':max(lat),'vectors_identical':all(v==emb[0] for v in emb)},'memory_trend':mem,'concurrency':{'requests':8,'parallel_workers':4,'results':conc,'errors':sum(r['http_status']>=400 for r in conc)}}
(OUT/'http_operations.json').write_text(json.dumps(summary,indent=2));print(json.dumps({'operations':[(r['name'],r['http_status'],r['body'].get('status')) for r in results[:20]],'warm_embedding':summary['warm_embedding'],'concurrency_errors':summary['concurrency']['errors']},indent=2))
