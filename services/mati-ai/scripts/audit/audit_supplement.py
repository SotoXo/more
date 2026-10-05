"""Audit-only color robustness, broad-family proxies and barcode exactness. No writes to images/DB."""
import io,json,hashlib,time
from pathlib import Path
from collections import Counter
import numpy as np
from PIL import Image,ImageDraw,ImageEnhance
import zxingcpp
from mati_ai.core.config import Settings
from mati_ai.contracts.vision import CatalogValue
from mati_ai.vision.validation import validate_image
from mati_ai.vision.color import extract_colors,FAMILY_ALIASES,_name
from mati_ai.vision.color_distance import delta_e_2000
from mati_ai.identification.codes import decode_codes
ROOT=Path(__file__).resolve().parents[4];OUT=ROOT/'storage/app/ai-audit'
manifest=json.loads((OUT/'evaluation_manifest.json').read_text());real=json.loads((OUT/'python-color-real.json').read_text())
sources={r['image_id']:r for r in manifest['images']};catalog=[CatalogValue.model_validate(r) for r in manifest['taxonomies']['colors']]
cfg=Settings(device='cpu',local_files_only=True,_env_file=None)
family_rows=[]
for row in real['baseline']:
 source=sources[row['image_id']];families={FAMILY_ALIASES.get(_name(c['fam_col'])) for c in source['color_labels']};families.discard(None)
 top=row['colorimetry']['palette'][0] if row['colorimetry']['palette'] else None;primary=row['colorimetry']['primary_color']
 family_rows.append({'image_id':row['image_id'],'expected_families':sorted(families),'top_family':top['family'] if top else None,'primary_family':primary['family'] if primary else None,'top_correct_proxy':bool(top and top['family'] in families),'primary_correct_proxy':bool(primary and primary['family'] in families)})
# Ordered diagnostic sample: first 20 frozen results with a primary. No selection on correctness.
selected=[r for r in real['baseline'] if r['colorimetry']['primary_color']][:20];rows=[]
for r in selected:
 source=sources[r['image_id']];raw=Path(source['local_path']).read_bytes();before=hashlib.sha256(raw).hexdigest();img=validate_image(raw,cfg).image;arr=np.asarray(img).astype(float)
 stream=io.BytesIO();img.save(stream,'JPEG',quality=60)
 transforms={'brightness_090':ImageEnhance.Brightness(img).enhance(.9),'brightness_110':ImageEnhance.Brightness(img).enhance(1.1),'warm_cast_5pct':Image.fromarray(np.clip(arr*[1.05,1,.95],0,255).astype(np.uint8)),'cool_cast_5pct':Image.fromarray(np.clip(arr*[.95,1,1.05],0,255).astype(np.uint8)),'crop_10pct_each_border':img.crop((round(img.width*.1),round(img.height*.1),round(img.width*.9),round(img.height*.9))),'small_128':img.resize((max(16,round(img.width*128/max(img.size))),max(16,round(img.height*128/max(img.size))))),'jpeg_60':Image.open(io.BytesIO(stream.getvalue())).convert('RGB')}
 base=r['colorimetry'];bp=base['primary_color'];bt=base['palette'][0];bi={v['catalog_color_id'] for v in base['palette'] if v['catalog_color_id'] is not None};bf={v['family'] for v in base['palette']}
 for name,modified in transforms.items():
  start=time.perf_counter();current=extract_colors(modified,catalog)['colorimetry'];cp=current['primary_color'];ct=current['palette'][0] if current['palette'] else None;ci={v['catalog_color_id'] for v in current['palette'] if v['catalog_color_id'] is not None};cf={v['family'] for v in current['palette']}
  id_comparable=bp['catalog_color_id'] is not None and cp is not None and cp['catalog_color_id'] is not None
  rows.append({'image_id':r['image_id'],'transform':name,'status':current['status'],'primary_present':cp is not None,'primary_id_baseline':bp['catalog_color_id'],'primary_id_current':cp['catalog_color_id'] if cp else None,'primary_id_comparable':id_comparable,'primary_id_equal':id_comparable and bp['catalog_color_id']==cp['catalog_color_id'],'primary_family_comparable':cp is not None,'primary_family_equal':cp is not None and bp['family']==cp['family'],'top_family_comparable':ct is not None,'top_family_equal':ct is not None and bt['family']==ct['family'],'palette_catalog_jaccard':len(bi&ci)/len(bi|ci) if bi|ci else None,'palette_family_set_equal':bool(ct and bf==cf),'primary_delta_e':float(delta_e_2000(np.asarray(bp['lab']),np.asarray(cp['lab']))) if cp else None,'top_delta_e':float(delta_e_2000(np.asarray(bt['lab']),np.asarray(ct['lab']))) if ct else None,'warnings':current['warnings'],'duration_ms':(time.perf_counter()-start)*1000})
 if hashlib.sha256(Path(source['local_path']).read_bytes()).hexdigest()!=before:raise RuntimeError('Original file changed')
summaries={}
for name in transforms:
 group=[r for r in rows if r['transform']==name];stat={'n':len(group),'status_counts':dict(Counter(r['status'] for r in group)),'primary_survived_n':sum(r['primary_present'] for r in group),'baseline_primary_id_n':sum(r['primary_id_baseline'] is not None for r in group)}
 for stem in ['primary_id','primary_family','top_family']:
  n=sum(r[stem+'_comparable'] for r in group);ok=sum(r[stem+'_equal'] for r in group);stat[stem+'_comparable_n']=n;stat[stem+'_equal_n']=ok;stat[stem+'_agreement_when_comparable']=ok/n if n else None
 for k in ['primary_delta_e','top_delta_e','palette_catalog_jaccard']:
  vals=[r[k] for r in group if r[k] is not None];stat[k]={'n':len(vals),'mean':float(np.mean(vals)) if vals else None,'median':float(np.median(vals)) if vals else None,'p90':float(np.percentile(vals,90)) if vals else None}
 stat['palette_family_set_equal_n']=sum(r['palette_family_set_equal'] for r in group);summaries[name]=stat
output={'kind':'real_catalog_in_memory_color_diagnostic_no_physical_ground_truth','family_proxy_caveat':'Expected broad family from existing commercial fam_col; observed family can incorporate accepted catalog label, so not independent validation. Single commercial label does not prove monocolor.','family_proxy':{'n':len(family_rows),'expected_mapped_n':sum(bool(r['expected_families']) for r in family_rows),'top_comparable_n':sum(bool(r['expected_families']) and r['top_family'] is not None for r in family_rows),'top_correct_n':sum(r['top_correct_proxy'] for r in family_rows),'primary_comparable_n':sum(bool(r['expected_families']) and r['primary_family'] is not None for r in family_rows),'primary_correct_n':sum(r['primary_correct_proxy'] for r in family_rows),'rows':family_rows},'stability':{'selection':'first 20 existing evaluation rows with non-null primary, not selected on correctness; diagnostic subset not representative robustness estimate','baseline_image_ids':[r['image_id'] for r in selected],'n_images':len(selected),'n_perturbations':len(rows),'original_image_hashes_unchanged':True,'summary_by_transform':summaries,'rows':rows}}
fixtures=[('EAN-13',zxingcpp.BarcodeFormat.EAN13,'4006381333931'),('EAN-8',zxingcpp.BarcodeFormat.EAN8,'96385074'),('UPC-A',zxingcpp.BarcodeFormat.UPCA,'036000291452'),('UPC-E',zxingcpp.BarcodeFormat.UPCE,'04210007'),('CODE128',zxingcpp.BarcodeFormat.Code128,'MATI-ABC-123'),('QR',zxingcpp.BarcodeFormat.QRCode,'https://example.org/product/42')];codes_rows=[]
for sym,fmt,value in fixtures:
 im=Image.fromarray(np.asarray(zxingcpp.write_barcode_to_image(zxingcpp.create_barcode(value,fmt),scale=3))).convert('RGB');codes=decode_codes(im)['codes'];match=next((c for c in codes if c['normalized_value']==value),None)
 codes_rows.append({'expected_symbology':sym,'expected_value':value,'decoded':codes,'exact_value_correct':match is not None,'symbology_correct':bool(match and match['symbology']==sym),'valid_gtin_checksum':match['checksum_valid'] if match else None})
prior=json.loads((OUT/'python-synthetic-audit.json').read_text())['barcode']['rows'];checksum=[r for r in prior if r['symbology'] in ['EAN13','EAN8','UPCA','UPCE'] and r['expected_detected']]
output['barcode_exactness']={'kind':'synthetic_real_decoder','baseline_n':6,'baseline_exact_value_n':sum(r['exact_value_correct'] for r in codes_rows),'baseline_symbology_correct_n':sum(r['symbology_correct'] for r in codes_rows),'gtin_detected_under_transform_n':len(checksum),'gtin_checksum_valid_n':sum(r['checksum_valid'] is True for r in checksum),'non_gtin_checksum':'not_applicable_QR_CODE128_application_GTIN_checksum','baseline_rows':codes_rows}
(OUT/'audit_supplement.json').write_text(json.dumps(output,indent=2,ensure_ascii=False)+'\n')
# Diagnostic visual inspection only. No source image modification.
ids=[1,13,3,7,59,71,74,76,77,78,79,80,81,82,31,33,41,90];sheet=Image.new('RGB',(6*220,3*300),'white');draw=ImageDraw.Draw(sheet)
for i,imageid in enumerate(ids):
 im=Image.open(sources[imageid]['local_path']).convert('RGB');im.thumbnail((210,245));x=(i%6)*220;y=(i//6)*300;sheet.paste(im,(x,y+48));draw.text((x+4,y+3),f"ID {imageid}: {sources[imageid]['category_name']}",fill='black');draw.text((x+4,y+20),sources[imageid]['title'][:29],fill='black')
sheet.save(OUT/'diagnostic_images.png')
print(json.dumps({'family_proxy':{k:v for k,v in output['family_proxy'].items() if k!='rows'},'stability':summaries,'barcode':{k:v for k,v in output['barcode_exactness'].items() if k!='baseline_rows'}},indent=2))
