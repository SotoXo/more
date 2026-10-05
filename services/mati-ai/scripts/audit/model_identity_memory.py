"""Exact model identity, dtype and GPU process memory; one model at a time."""
from pathlib import Path
import json,time,gc,subprocess
import torch,numpy as np
from PIL import Image
from mati_ai.core.model_manager import ModelManager
from mati_ai.core.config import Settings
from mati_ai.vision.classification import category_prompts
from mati_ai.contracts.vision import CatalogValue
ROOT=Path('/home/soto/Code/more');out=ROOT/'storage/app/ai-audit';tax=json.loads((out/'catalog_taxonomies.json').read_text());prompts=category_prompts([CatalogValue.model_validate(r) for r in tax['categories']]);m=json.loads((out/'evaluation_manifest.json').read_text());row=m['images'][30];image=Image.open(row['local_path']).convert('RGB');report={'input_image_id':row['image_id'],'prompt_count':len(prompts),'sample_n':10,'results':{}}
for device in ['cuda','cpu']:
 gc.collect()
 if torch.cuda.is_available():torch.cuda.empty_cache()
 before={'global_nvidia_memory_used_mib':subprocess.check_output(['nvidia-smi','--query-gpu=memory.used','--format=csv,noheader,nounits'],text=True).strip(),'torch_allocated_mib':torch.cuda.memory_allocated()/1024**2,'torch_reserved_mib':torch.cuda.memory_reserved()/1024**2}
 manager=ModelManager(Settings(_env_file=None,device=device,local_files_only=True));start=time.perf_counter();manager._load();cold=(time.perf_counter()-start)*1000;parameter=next(manager._model.parameters());dtype=str(parameter.dtype);del parameter
 after={'torch_allocated_mib':torch.cuda.memory_allocated()/1024**2,'torch_reserved_mib':torch.cuda.memory_reserved()/1024**2}
 samples=[]
 for i in range(10):
  result=manager.infer(image,prompts);samples.append({'i':i,'ms':result['metrics']['inference'],'vram_peak_mib':result['vram_peak_mb'],'torch_allocated_mib':torch.cuda.memory_allocated()/1024**2})
 lat=[r['ms'] for r in samples[1:]]
 report['results'][device]={'device_used':manager.device,'dtype':dtype,'identity':manager.identity,'dimensions':len(result['embedding']),'norm':float(np.linalg.norm(result['embedding'])),'cold_load_ms':cold,'before_load':before,'after_load':after,'first_inference_ms':samples[0]['ms'],'warm_ms':{'n':len(lat),'min':min(lat),'mean':np.mean(lat).item(),'p50':np.percentile(lat,50).item(),'p95':np.percentile(lat,95).item(),'max':max(lat)},'samples':samples}
 del result,manager;gc.collect()
 if torch.cuda.is_available():torch.cuda.empty_cache()
(out/'model_identity_memory.json').write_text(json.dumps(report,indent=2));print(json.dumps({device:{k:v for k,v in r.items() if k!='samples'} for device,r in report['results'].items()},indent=2))
