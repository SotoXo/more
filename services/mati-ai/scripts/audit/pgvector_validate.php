<?php
// Read real embeddings, validate only explicitly isolated database. Password is environment-only.
require '/var/www/html/vendor/autoload.php';
$app=require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
Config::set('database.default','pgsql');
Config::set('database.connections.pgsql.host','more-ai-audit-pg-20261004');
Config::set('database.connections.pgsql.database','testing');
Config::set('database.connections.pgsql.username','ai_audit');
Config::set('database.connections.pgsql.password',getenv('AUDIT_DB_PASSWORD'));
DB::purge('pgsql');
if(DB::selectOne('SELECT current_database() AS db')->db!=='testing'
    ||config('database.connections.pgsql.host')!=='more-ai-audit-pg-20261004')throw new RuntimeException('ISOLATION_ASSERTION_FAILED');
$store=app(App\Services\AI\CatalogEmbeddingStore::class);
$reviews=app(App\Services\AI\AiReviewService::class);
$sample=json_decode(file_get_contents('/var/www/html/storage/app/ai-audit/embedding_sample.json'),true);
$item=$sample[0];$e=$item['embedding'];$checks=[];
$expect=function($name,$fn,$expected)use(&$checks){
    try{$fn();$checks[$name]=['passed'=>false,'observed'=>'NO_EXCEPTION','expected'=>$expected];}
    catch(Throwable $x){
        $observed=$x instanceof Illuminate\Database\QueryException?(string)$x->getCode():$x->getMessage();
        $checks[$name]=['passed'=>str_contains($observed,$expected),'observed'=>$observed,'expected'=>$expected];
    }
};
$expect('reject_empty_values',fn()=>$store->prepare([...$e,'values'=>[]]),'INVALID_EMBEDDING_VALUES');
$expect('reject_zero_vector',fn()=>$store->prepare([...$e,'values'=>array_fill(0,768,0)]),'ZERO_EMBEDDING_VECTOR');
$expect('reject_nonfinite',fn()=>$store->prepare([...$e,'values'=>[INF],'dimension'=>1]),'INVALID_EMBEDDING_VALUES');
$expect('reject_declared_dimensions',fn()=>$store->prepare([...$e,'dimension'=>767]),'EMBEDDING_DIMENSION_MISMATCH');
$expect('reject_unpinned_revision',fn()=>$store->prepare([...$e,'model_revision'=>'main']),'UNPINNED_EMBEDDING_REVISION');
$expect('reject_l1_normalization',fn()=>$store->prepare([...$e,'normalization'=>'l1']),'UNSUPPORTED_EMBEDDING_NORMALIZATION');
$expect('reject_model_revision_disagreement',fn()=>$store->prepare($e,['model_revision'=>str_repeat('a',40)]),'INCOMPLETE_EMBEDDING_IDENTITY');
$captured=null;
DB::listen(function($event)use(&$captured){
    if(str_contains($event->sql,'WITH eligible AS MATERIALIZED')&&!str_starts_with($event->sql,'EXPLAIN'))$captured=[$event->sql,$event->bindings];
});
$store->search($e,['exclude_prenda_id'=>$item['prenda_id'],'limit'=>12]);
$searchSql=$captured[0];
$plan=DB::select('EXPLAIN (ANALYZE,BUFFERS,FORMAT JSON) '.$captured[0],$captured[1]);
$explain=json_decode($plan[0]->{'QUERY PLAN'},true)[0];
DB::beginTransaction();
try{
    $make=function($embedding,$operation)use($reviews,$store,$item){
        $model=array_intersect_key($embedding,array_flip(['model_id','model_revision','pipeline_version']));
        $r=$reviews->persist($operation,[
            'request_id'=>(string)Str::uuid(),'contract_version'=>'1','status'=>'completed',
            'data'=>['embedding'=>$embedding],'model'=>$model,'warnings'=>[],
        ],20,['sha256'=>$item['sha256'],'image_id'=>$item['image_id'],'audit_synthetic'=>true]);
        $r->update(['prenda_id'=>$item['prenda_id']]);
        return [$r,$store->storeRun($r,$item['image_id'],['audit_synthetic'=>true])];
    };
    [$mixedRun,$mixedRow]=$make([...$e,'dimension'=>2,'values'=>[1.0,0.0]],'audit-synthetic-geometry');
    $r=$store->search($e,['exclude_prenda_id'=>$item['prenda_id'],'limit'=>30]);
    $checks['mixed_dimension_never_compared']=[
        'passed'=>$r['status']==='completed'&&$r['total_candidates']===129,
        'observed_status'=>$r['status'],'observed_candidates'=>$r['total_candidates'],
    ];
    [$otherRun,$otherRow]=$make([...$e,'model_revision'=>str_repeat('a',40)],'audit-synthetic-revision');
    $r=$store->search([...$e,'model_revision'=>str_repeat('b',40)],['limit'=>12]);
    $checks['different_revision_empty_compatible_space']=[
        'passed'=>$r['status']==='insufficient_evidence'&&$r['total_candidates']===0
            &&in_array('VECTOR_COMPATIBLE_INDEX_EMPTY',$r['warnings']),
        'status'=>$r['status'],'warnings'=>$r['warnings'],
    ];
    $r=$store->search([...$e,'preprocess_version'=>'audit-synthetic-other-preprocess'],['limit'=>12]);
    $checks['different_preprocess_empty_compatible_space']=['passed'=>$r['total_candidates']===0,'status'=>$r['status']];
    $id=DB::table('ai_product_embeddings')->where('source_run_id','<>',$mixedRun->id)->where('dimensions',768)->orderBy('id')->value('id');
    $expect('database_check_dimensions',fn()=>DB::transaction(fn()=>DB::table('ai_product_embeddings')->where('id',$id)->update(['dimensions'=>767])),'23514');
    $expect('database_check_l2_norm',fn()=>DB::transaction(fn()=>DB::table('ai_product_embeddings')->where('id',$id)->update(['norm'=>2])),'23514');
    $expect('database_foreign_product',fn()=>DB::transaction(fn()=>DB::table('ai_product_embeddings')->where('id',$id)->update(['prenda_id'=>999999999])),'23503');
    $expect('image_product_mismatch',fn()=>$store->storeRun($mixedRun,$sample[1]['image_id']),'EMBEDDING_IMAGE_PRODUCT_MISMATCH');
    $originalModel=$mixedRun->model_version_id;
    $mixedRun->update(['model_version_id'=>$otherRun->model_version_id]);
    $expect('model_version_fk_identity_mismatch',fn()=>$store->storeRun($mixedRun,$item['image_id']),'EMBEDDING_MODEL_VERSION_MISMATCH');
    $mixedRun->update(['model_version_id'=>$originalModel]);
    DB::table('imagenes_prenda')->where('id_imagen_prenda',$item['image_id'])->update(['rut_igp'=>'productos/audit-synthetic-replaced-path.webp']);
    $r=$store->search($e,['limit'=>30]);
    $checks['replaced_image_path_excludes_prior_vectors']=['passed'=>$r['total_candidates']===129,'observed_candidates'=>$r['total_candidates']];
    DB::table('imagenes_prenda')->where('id_imagen_prenda',$item['image_id'])->update(['rut_igp'=>data_get($mixedRow->metadata,'image_path')]);
    $colorIds=DB::table('colores')->orderBy('id_color')->limit(2)->pluck('id_color')->all();
    $sizeIds=DB::table('tallas')->orderBy('id_talla')->limit(2)->pluck('id_talla')->all();
    $variant=DB::table('prenda_variantes')->where('cod_prendas_prv',$item['prenda_id'])->first();
    DB::table('prenda_variantes')->where('id_prenda_variante',$variant->id_prenda_variante)->update(['cod_colores_prv'=>$colorIds[0],'cod_tallas_prv'=>$sizeIds[0]]);
    $variantData=(array)$variant;unset($variantData['id_prenda_variante']);
    $variantData['sku_prv']='AUDIT-'.substr((string)Str::uuid(),0,20);
    $variantData['cob_prv']=null;$variantData['cod_colores_prv']=$colorIds[1];$variantData['cod_tallas_prv']=$sizeIds[1];
    DB::table('prenda_variantes')->insert($variantData);
    $r=$store->search($e,['color_id'=>$colorIds[0],'size_id'=>$sizeIds[1],'limit'=>30]);
    $wrong=collect($r['matches'])->contains('prenda_id',$item['prenda_id']);
    $checks['independent_color_size_exists_wrong_variant_REPRODUCED']=[
        'passed'=>$wrong,'observed_product_returned_without_matching_combination'=>$wrong,
        'classification'=>'P2 service semantics bug; endpoint UI does not expose combination filters',
    ];
}finally{DB::rollBack();}
$audit=[
    'database'=>'testing',
    'pgvector_version'=>DB::selectOne("SELECT extversion FROM pg_extension WHERE extname='vector'")->extversion,
    'checks'=>$checks,'test_count'=>count($checks),
    'passed_count'=>count(array_filter($checks,fn($x)=>$x['passed'])),
    'post_rollback_vector_count'=>DB::table('ai_product_embeddings')->count(),
    'explain_planning_ms'=>$explain['Planning Time'],'explain_execution_ms'=>$explain['Execution Time'],
    'explain_actual_sql'=>$searchSql,'explain_plan'=>$explain,
    'notes'=>'Negative probes synthetic and transaction rolled back. EXPLAIN SELECT production search with real vector. Color+size confirms independent EXISTS cannot enforce one variant.',
];
file_put_contents('/var/www/html/storage/app/ai-audit/pgvector_negative_explain_audit.json',json_encode($audit,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo json_encode(array_diff_key($audit,['explain_actual_sql'=>1,'explain_plan'=>1]),JSON_PRETTY_PRINT).PHP_EOL;
