<?php
declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use App\Models\ImagenPrenda;
use App\Services\AI\CatalogImageReader;
require dirname(__DIR__,4).'/vendor/autoload.php';
$app=require dirname(__DIR__,4).'/bootstrap/app.php';$app->make(Kernel::class)->bootstrap();
$phase=$argv[1]??'';
if(!in_array($phase,['before','after'],true))throw new RuntimeException('EXPLICIT_PHASE_REQUIRED');
$out=storage_path('app/ai-rag-gate-v2/preservation-'.$phase.'.json');
if(file_exists($out))throw new RuntimeException('EVIDENCE_ALREADY_EXISTS');
if(DB::connection()->getDriverName()!=='pgsql'||config('database.connections.pgsql.host')!=='pgsql'||DB::selectOne('SELECT current_database() AS name')->name!=='laravel')throw new RuntimeException('LOCAL_READ_ONLY_DATABASE_REQUIRED');
DB::beginTransaction();DB::statement('SET TRANSACTION READ ONLY');
try {
$baseline=json_decode(file_get_contents(storage_path('app/ai-semantic/catalog-final.json')),true,512,JSON_THROW_ON_ERROR);
$fingerprints=[];
foreach($baseline['commercial_fingerprints'] as $table=>$evidence){
 if(!preg_match('/^[a-z_]+$/',$table)||!preg_match('/^[a-z_ ,]+$/',$evidence['columns']))throw new RuntimeException('INVALID_FIXED_BASELINE');
 $row=DB::selectOne("SELECT count(*) AS count,COALESCE(string_agg(row_to_json(t)::text,E'\\n' ORDER BY row_to_json(t)::text),'') AS payload FROM (SELECT ".$evidence['columns']." FROM ".$table.") t");
 $fingerprints[$table]=['columns'=>$evidence['columns'],'count'=>(int)$row->count,'sha256'=>hash('sha256',$row->payload)];
}
$canonical=DB::select("SELECT id,prenda_id,image_id,ai_model_version_id,source_run_id,content_hash,dimensions,geometry_key,embedding_version,preprocess_version,normalization,norm,input_norm,md5(embedding::text) AS vector_text_md5,md5(metadata::text) AS metadata_md5,created_at,updated_at FROM ai_product_embeddings ORDER BY id");
$canonical=json_decode(json_encode($canonical,JSON_THROW_ON_ERROR),true,512,JSON_THROW_ON_ERROR);
$aiCounts=[];
foreach(['ai_model_versions','ai_runs','ai_suggestions','ai_human_reviews','ai_product_embeddings'] as $t)$aiCounts[$t]=DB::table($t)->count();
$imageHashes=[];$reader=app(CatalogImageReader::class);
foreach(ImagenPrenda::where('rut_igp','like','productos/%')->orderBy('id_imagen_prenda')->get() as $image)$imageHashes[]=['image_id'=>(int)$image->id_imagen_prenda,'product_id'=>(int)$image->cod_prendas_igp,'path'=>$image->rut_igp,'sha256'=>hash('sha256',$reader->read($image))];
$rag=[];
foreach(['rag_model_versions','rag_documents','rag_chunks','rag_embeddings'] as $t){
 $rows=DB::table($t)->orderBy('id')->get()->all();$rag[$t]=['count'=>count($rows),'sha256'=>hash('sha256',json_encode($rows,JSON_THROW_ON_ERROR))];
}
$checks=['commercial_34_tables_identical'=>$fingerprints===$baseline['commercial_fingerprints'],'visual_134_rows_identical'=>$canonical===$baseline['canonical_rows'],'ai_counts_identical'=>$aiCounts===$baseline['ai_counts'],'source_images_identical'=>$imageHashes===$baseline['image_hashes'],'rag_1_23_23_23'=>array_column($rag,'count')===[1,23,23,23]];
if($phase==='after'){
 $before=json_decode(file_get_contents(storage_path('app/ai-rag-gate-v2/preservation-before.json')),true,512,JSON_THROW_ON_ERROR);
 $checks['rag_rows_identical']=$rag===$before['rag_fingerprints'];
}
$data=['utc'=>gmdate('c'),'phase'=>$phase,'read_only'=>true,'checks'=>$checks,'commercial_fingerprints'=>$fingerprints,'ai_counts'=>$aiCounts,'canonical_sha256'=>hash('sha256',json_encode($canonical,JSON_THROW_ON_ERROR)),'source_images_sha256'=>hash('sha256',json_encode($imageHashes,JSON_THROW_ON_ERROR)),'rag_fingerprints'=>$rag,'passed'=>!in_array(false,$checks,true)];
DB::rollBack();
file_put_contents($out,json_encode($data,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");chmod($out,0600);
echo json_encode(['phase'=>$phase,'checks'=>$checks,'ai_counts'=>$aiCounts,'rag_counts'=>array_map(fn($r)=>$r['count'],$rag),'passed'=>$data['passed']],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
exit($data['passed']?0:1);
}catch(Throwable $error){DB::rollBack();throw $error;}
