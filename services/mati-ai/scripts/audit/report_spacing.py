"""Editorial spacing for audit Markdown only. Protected paths, identifiers and numeric values."""
import ast,re,json,collections
from pathlib import Path
ROOT=Path(__file__).resolve().parents[4];D=ROOT/'DOCUMENTACION/IA/AUDITORIA_FINAL';O=ROOT/'storage/app/ai-audit'
freq=collections.Counter()
for name in ['00_como_funciona_toda_la_ia.md','04_auditoria_colorimetria.md','05_auditoria_embeddings_pgvector.md','06_auditoria_ocr_barcode_matching.md','07_auditoria_integracion_e2e.md','_python_inspeccion.md','_laravel_inspeccion.md','_frontend_inspeccion.md']:
 freq.update(w.lower() for w in re.findall(r'\b[^\W\d_]+\b',(D/name).read_text().split('## Suplemento final:')[0]))
lex={w for w,n in freq.items() if n>=2 and len(w)>=3};lex.update('a al con de del el en es la las lo los no o por se si sin su un una y'.split());lex.update('actual actuales activo activos aislado aislada antes después aplicación archivo archivos campos cambio cambios comparación componente datos decisión decisiones devuelve devuelto evento evidencia evidencias falla falso falta faltantes filas fórmula guardado guardados imagen imágenes información interpretación límite límites matriz máximo medida medidos migración muestra nuevo nuevas observa observada observados operativo operativos porcentaje producto prueba pruebas puro rango resultados resultado score scores servicio script sólo tabla tablas tiempo tokens transforma transformación transformaciones transcrito validación válido válidos valor valores variante variantes visualización reales real respuesta respuestas request key privado privadas fuente fuentes existe usuario futuras futuros semántica supervivencia correcto correcta completo igual ausencia ausente detectados identificador original originales nuestro usada referencia todos variables motor produce motor palabras otra otro'.split())
protected={'FakeModel','ModelManager','EasyOCRProvider','MatiAiClient','SigLIP','CIEDE','TopK','Top1','Top3','Levenshtein','QuickResponse','GarmentColorimetry'}
for f in (ROOT/'app').rglob('*.php'):protected.update(re.findall(r'\bclass\s+(\w+)',f.read_text()))
for f in (ROOT/'services/mati-ai/src').rglob('*.py'):
 for node in ast.walk(ast.parse(f.read_text())):
  if isinstance(node,(ast.ClassDef,ast.FunctionDef,ast.AsyncFunctionDef)):protected.add(node.name)
valid={'parciales','identidades','controlados','contenedores','estados','demostró','conservados','prioridades','totales','normalizados','contexto','generados','tensores','solaparse','deduplicado','deduplicar','superar','implementar','disponibilidad','independientes','invariantes','variables','desbalance','locales','independiente','clasificación','interno','externo','procesamiento','transcripciones','información','cantidades','ambas','matemática'}
changes=collections.Counter()
def split(w):
 low=w.lower()
 if len(w)<7 or low in lex or low in valid or w in protected:return w
 cache={len(low):[]}
 def rec(i):
  if i in cache:return cache[i]
  for j in range(len(low),i+1,-1):
   if low[i:j] in lex:
    suffix=rec(j)
    if suffix is not None:cache[i]=[w[i:j]]+suffix;return cache[i]
  cache[i]=None;return None
 pieces=rec(0)
 if not pieces or len(pieces)<2 or pieces[-1].lower() in ['es','dos','se','a','o']:return w
 if any(len(x)<3 and x.lower() not in ['al','con','de','del','el','en','la','las','lo','los','no','por','si','sin','su','un','una'] for x in pieces):return w
 return ' '.join(pieces)
for name in ['01_resultados_validacion.md','02_diccionario_de_metricas.md','03_benchmark_modelos.md','08_incoherencias_y_riesgos.md','09_plan_de_correccion_priorizado.md','10_resumen_para_defensa.md','04_auditoria_colorimetria.md','05_auditoria_embeddings_pgvector.md','06_auditoria_ocr_barcode_matching.md','07_auditoria_integracion_e2e.md']:
 p=D/name;t=p.read_text();original=t
 def repl(m):
  w=m.group();a,b=m.span()
  # Do not edit paths, file extensions, inline identifiers or formulas with underscores.
  if (a>0 and t[a-1] in '/_\\`') or (b<len(t) and t[b] in '/_\\`') or (b<len(t)-1 and t[b]=='.' and t[b+1].isalpha()):return w
  v=split(w)
  if v!=w:changes[(w,v)]+=1
  return v
 t=re.sub(r'\b[^\W\d_]+\b',repl,t)
 # Deliberately restricted count-word spacing, not checkpoint hashes/version strings.
 t=re.sub(r'(\d{1,4})(?=(?:fotos|prendas|imágenes|variantes|inferencias|muestras|queries|vectores|pares|prompts|paletas|categorías|marcas|etiquetas|transformaciones|casos|archivos|tablas|errores|workers|requests|candidatos|assertions|tests|textos|checks|fuentes|campos|eventos|controles|originales|calientes|datasets|fallas|pasos|decisiones|productos)\b)',r'\1 ',t)
 for a,b in {'SigLIPCUDA':'SigLIP CUDA','coverage10%':'coverage 10%','score1':'score 1','score0.282':'score 0.282','N9device':'N9/device','nofiltrados':'no filtrados','nodecodificaron':'no decodificaron','nodejó':'no dejó','enloscinco':'en los cinco','contoken':'con token','sinFastAPI':'sin FastAPI','conmodelo':'con modelo','depeso':'de peso','deimagen':'de imagen','deoutfit':'de outfit','deloutfit':'del outfit','deconfianza':'de confianza','noesfalse':'no es false','noes':'no es','GTINdetectados':'GTIN detectados','noentrenar':'no entrenar','noladecisión':'no la decisión','sinaplicar':'sin aplicar','sin guardarproductos':'sin guardar productos','porfilas':'por filas','UIporimagen':'UI por imagen','tipoGTIN':'tipo GTIN','fieldtyped':'field typed','CPUtrasOS':'CPU tras OS','noguardar':'no guardar','DockerN1':'Docker N1','P1inicial':'P1 inicial','APIprivada':'API privada','labelpartial':'label partial','MainHTTP':'Main HTTP'}.items():t=t.replace(a,b)
 if t!=original:p.write_text(t)
(O/'report_editorial_spacing.json').write_text(json.dumps([{'before':a,'after':b,'count':n} for (a,b),n in changes.items()],indent=2,ensure_ascii=False)+'\n')
# Scientific clarification: internal components are 0..1, exposed components are 0..100.
p=D/'02_diccionario_de_metricas.md';t=p.read_text().replace('100×Σ(w_jcomponent_j)/Σw_j disponibles','100×Σ(w_j c_j)/Σw_j disponibles, c_j interno en[0,1]');t=t.replace('clusters separados descartados cambian denominador','clusters pequeños descartados reducen coverage, sin renormalizar percentage; compatibilidad sí normaliza la paleta retenida');p.write_text(t)
p=D/'00_como_funciona_toda_la_ia.md';t=p.read_text().replace('http/https','HTTPS (fetch externo rechaza HTTP)');p.write_text(t)
print('Editorial corrections',len(changes))
