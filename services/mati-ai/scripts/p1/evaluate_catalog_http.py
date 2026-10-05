"""Audit baseline via real HTTP; no database/product writes, no downloaded weights."""
import hashlib,json,time,subprocess
from pathlib import Path
from collections import defaultdict,Counter
import httpx,numpy as np
from sklearn.metrics import accuracy_score,precision_recall_fscore_support,confusion_matrix
ROOT=Path('/home/soto/Code/more');OUT=ROOT/'storage/app/ai-p1'
manifest_path=ROOT/'storage/app/ai-audit/evaluation_manifest.json'; manifest=json.loads(manifest_path.read_text());rows=manifest['images'];tax=manifest['taxonomies']
env=dict(line.split('=',1) for line in (ROOT/'.env').read_text().splitlines() if line and not line.startswith('#') and '=' in line);client=httpx.Client(base_url='http://127.0.0.1:8001',timeout=90,headers={'Authorization':'Bearer '+env['MATI_AI_TOKEN']})
groups=defaultdict(list)
for row in rows:groups[row['sha256']].append(row)
selected=[];excluded=[]
for sha,items in groups.items():
 if len({i['category_id'] for i in items})>1:excluded.append({'sha256':sha,'reason':'conflicting_catalog_category_labels','image_ids':[i['image_id'] for i in items]});continue
 if items[0]['eligible_classification']:selected.append(items[0])
results=[]
for i,row in enumerate(selected):
 p=Path(row['local_path']);start=time.perf_counter();r=client.post('/api/v1/garments/analyze',files={'image':(p.name,p.read_bytes(),row['mime'])},data={'categories':json.dumps(tax['categories']),'colors':json.dumps(tax['colors']),'patterns':json.dumps(tax['patterns']),'include_embedding':'true'})
 result={'image_id':row['image_id'],'product_id':row['prenda_id'],'category_id':row['category_id'],'category_name':row['category_name'],'sha256':row['sha256'],'http_status':r.status_code,'wall_ms':(time.perf_counter()-start)*1000,'response':r.json()}
 results.append(result)
 with (OUT/'catalog_http_predictions.jsonl').open('w' if i==0 else 'a') as f:f.write(json.dumps(result)+'\n')
 if (i+1)%20==0:print('catalog evaluated',i+1,'/',len(selected),flush=True)
valid=[r for r in results if r['http_status']==200 and r['response'].get('data',{}).get('classification',{}).get('top_k')]
y=[r['category_id'] for r in valid];pred=[r['response']['data']['classification']['top_k'][0]['category_id'] for r in valid]
labels=sorted({r['category_id'] for r in selected});p,rec,f,s=precision_recall_fscore_support(y,pred,labels=labels,zero_division=0)
mp,mr,mf,_=precision_recall_fscore_support(y,pred,labels=labels,average='macro',zero_division=0);_,_,wf,_=precision_recall_fscore_support(y,pred,labels=labels,average='weighted',zero_division=0)
covered=[r for r in valid if not r['response']['data']['classification']['abstained']]
lat=[r['wall_ms'] for r in results];scores=[v['similarity_score'] for r in valid for v in r['response']['data']['classification']['top_k']]
metric={'dataset':'all_existing_valid_catalog_primary_images_sha_deduplicated','manifest_sha256':hashlib.sha256(manifest_path.read_bytes()).hexdigest(),'commercial_labels_without_independent_expert_review':True,'n_catalog_images':len(rows),'n_unique_sha':len(groups),'n_evaluated':len(selected),'n_ranked':len(valid),'excluded_conflicting_sha':excluded,'top1_ranked_accuracy':accuracy_score(y,pred) if y else None,'top3_ranked_accuracy':np.mean([r['category_id'] in [v['category_id'] for v in r['response']['data']['classification']['top_k']] for r in valid]).item() if valid else None,'macro_precision':mp,'macro_recall':mr,'macro_f1':mf,'weighted_f1':wf,'coverage':len(covered)/len(selected),'covered_n':len(covered),'covered_accuracy':np.mean([r['category_id']==r['response']['data']['classification']['top_k'][0]['category_id'] for r in covered]).item() if covered else None,'class_labels':labels,'confusion_matrix':confusion_matrix(y,pred,labels=labels).tolist(),'per_class':[{'id':int(l),'name':next(t['name'] for t in tax['categories'] if t['id']==l),'precision':float(a),'recall':float(b),'f1':float(c),'support':int(d)} for l,a,b,c,d in zip(labels,p,rec,f,s)],'wall_latency_ms':{'n':len(lat),'min':min(lat),'mean':np.mean(lat).item(),'p50':np.percentile(lat,50).item(),'p95':np.percentile(lat,95).item(),'max':max(lat)},'score_observed_min':min(scores) if scores else None,'score_observed_max':max(scores) if scores else None,'model':valid[0]['response']['model'] if valid else None,'http_success_rate':sum(r['http_status']==200 for r in results)/len(results),'completed_rate':sum(r['response'].get('status')=='completed' for r in results)/len(results),'partial_rate':sum(r['response'].get('status')=='partial' for r in results)/len(results),'warnings':dict(Counter(w for r in results for w in r['response'].get('warnings',[]))),'abstentions':dict(Counter(w for r in results for w in r['response'].get('abstentions',[])))}
(OUT/'classification_metrics.json').write_text(json.dumps(metric,indent=2))
(OUT/'classification_predictions.json').write_text(json.dumps(results,indent=2))
(OUT/'embedding_sample.json').write_text(json.dumps([{'image_id':r['image_id'],'prenda_id':r['product_id'],'sha256':r['sha256'],'embedding':r['response']['data'].get('embedding')} for r in valid],indent=2))
print(json.dumps({k:v for k,v in metric.items() if k not in ['confusion_matrix','per_class','warnings','abstentions']},indent=2),flush=True)
