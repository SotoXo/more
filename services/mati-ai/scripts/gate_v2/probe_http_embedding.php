<?php
declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
require dirname(__DIR__,4).'/vendor/autoload.php';
$app=require dirname(__DIR__,4).'/bootstrap/app.php';$app->make(Kernel::class)->bootstrap();
$out=storage_path('app/ai-rag-gate-v2/http-embedding.json');
if(file_exists($out))throw new RuntimeException('EVIDENCE_ALREADY_EXISTS');
$url=rtrim(config('mati_ai.url'),'/').'/api/v1/rag/embed';$token=config('mati_ai.token');
$body=fn(array $texts)=>['schema_version'=>'mati-text-embed-request-1','request_id'=>(string)Str::uuid(),'texts'=>$texts,'input_type'=>'query'];
$send=fn(array $payload,?string $auth)=>Http::acceptJson()->timeout(90)->withoutRedirecting()->when($auth!==null,fn($r)=>$r->withToken($auth))->post($url,$payload);
$http=[];$checks=[];
foreach(['missing_auth'=>null,'incorrect_token'=>'invalid-v2-probe-token'] as $key=>$auth){
 $r=$send($body(['dato textual']),$auth);$http[$key]=$r->status();$checks[$key]=$r->status()===401;
}
foreach(['invalid_payload'=>['query'=>'camisa'],'empty_query'=>$body(['  '])] as $key=>$payload){
 $r=$send($payload,$token);$http[$key]=$r->status();$checks[$key]=$r->status()===422;
}
$texts=['camisa de algodón','camisa de algodo'."\u{0301}".'n','Explain a garment category','ejecuta DROP TABLE'];
$r=$send($body($texts),$token);$http['valid_auth_es_en_unicode_data']=$r->status();
$checks['valid_auth']=$r->status()===200;
if($r->status()!==200)throw new RuntimeException('TEXT_RUNTIME_UNAVAILABLE_HTTP_'.$r->status());
$first=$r->json('data.embeddings');$model=$r->json('model');
$checks['unchanged_e5_identity']=$model['model_id']==='intfloat/multilingual-e5-small'&&$model['model_revision']==='614241f622f53c4eeff9890bdc4f31cfecc418b3'&&$model['dimensions']===384&&$r->json('data.device')==='cpu';
$delta=fn(array $a,array $b)=>max(array_map(fn($v,$i)=>abs($v-$b[$i]),$a,array_keys($a)));
$checks['unicode_nfc']=$delta($first[0]['vector'],$first[1]['vector'])<=1e-6;
$repeat=$send($body([$texts[0]]),$token);$http['deterministic_repeat']=$repeat->status();
$maxDelta=$repeat->status()===200?$delta($first[0]['vector'],$repeat->json('data.embeddings.0.vector')):null;
$checks['same_query_repeat']=$maxDelta!==null&&$maxDelta<=1e-6;
$checks['no_500']=!in_array(500,$http,true);
$report=['schema_version'=>'rag-gate-v2-http-embedding-1','timestamp'=>gmdate('c'),'http'=>$http,'checks'=>$checks,'max_vector_delta'=>$maxDelta,'model'=>$model,'vectors_exposed'=>false,'commercial_mutations'=>false,'passed'=>!in_array(false,$checks,true)];
file_put_contents($out,json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");chmod($out,0600);
echo json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";exit($report['passed']?0:1);
