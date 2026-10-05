"""Compare captured real PostgreSQL results with NumPy cosine brute force."""
import json,hashlib
from pathlib import Path
import numpy as np
ROOT=Path('/home/soto/Code/more');out=ROOT/'storage/app/ai-audit'
rows=json.loads((out/'embedding_sample.json').read_text());sql=json.loads((out/'pgvector_sql_neighbors.json').read_text());ids=np.array([r['prenda_id'] for r in rows]);vectors=np.array([r['embedding']['values'] for r in rows],dtype=np.float64);vectors=vectors/np.linalg.norm(vectors,axis=1,keepdims=True)
checks=[];errors=[]
for query in sql['queries']:
 q=next(i for i,r in enumerate(rows) if r['image_id']==query['query_image_id']);scores=vectors@vectors[q];scores[q]=-np.inf
 order=np.lexsort((ids,-scores));expected=[int(ids[i]) for i in order if ids[i]!=query['exclude_prenda_id'] and scores[i]>=query['min_similarity']][:query['limit']];observed=[r['prenda_id'] for r in query['matches']]
 checks.append({'query_image_id':query['query_image_id'],'exact_order_agreement':expected==observed,'top_k_set_overlap':len(set(expected)&set(observed))/len(expected) if expected else 1.0})
 errors.extend(abs(float(scores[np.flatnonzero(ids==r['prenda_id'])[0]])-r['similarity_score']) for r in query['matches'])
report={'implementation':'NumPy float64 L2 normalized cosine brute force vs captured real pgvector SQL','numpy':np.__version__,'sample_n':len(rows),'query_n':len(checks),'k':30,'exact_order_agreement_rate':sum(r['exact_order_agreement'] for r in checks)/len(checks),'top_k_set_agreement_rate':sum(r['top_k_set_overlap'] for r in checks)/len(checks),'max_absolute_cosine_error':max(errors),'sample_sha256':hashlib.sha256((out/'embedding_sample.json').read_bytes()).hexdigest(),'sql_capture_sha256':hashlib.sha256((out/'pgvector_sql_neighbors.json').read_bytes()).hexdigest(),'checks':checks}
(out/'numpy_pgvector_agreement.json').write_text(json.dumps(report,indent=2));print(json.dumps({k:v for k,v in report.items() if k!='checks'},indent=2))
