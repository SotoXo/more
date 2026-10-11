<?php
declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Str;
require dirname(__DIR__,4).'/vendor/autoload.php';
$app=require dirname(__DIR__,4).'/bootstrap/app.php';$app->make(Kernel::class)->bootstrap();
if (!in_array('--execute-explicit',$argv,true)) throw new RuntimeException('Explicit runtime probe required');
$out=storage_path('app/ai-semantic/text-runtime.json');if(file_exists($out))throw new RuntimeException('Evidence already exists');
$url=rtrim(config('mati_ai.url'),'/').'/api/v1/rag/embed';$token=config('mati_ai.token');
$body=fn(array $texts,string $type='query')=>['schema_version'=>'mati-text-embed-request-1','request_id'=>(string)Str::uuid(),'texts'=>$texts,'input_type'=>$type];
$send=fn(array $payload,?string $auth=null)=>Http::acceptJson()->timeout(90)->withoutRedirecting()->when($auth!==null,fn($request)=>$request->withToken($auth))->post($url,$payload);
$http=[];$check=[];$timing=[];
foreach(['missing_token'=>null,'wrong_token'=>'invalid-runtime-probe-token']as$key=>$auth){$r=$send($body(['camisa']),$auth);$http[$key]=$r->status();$check[$key]=$r->status()===401;}
$texts=['camisa de algodón azul','camisa de algodo'."\u{0301}".'n azul'];
$start=microtime(true);$valid=$send($body($texts),$token);$timing['first_request_wall_ms']=(microtime(true)-$start)*1000;
if($valid->status()!==200)throw new RuntimeException('Approved model unavailable HTTP '.$valid->status());
$http['valid_unicode']=$valid->status();$data=$valid->json('data');$model=$valid->json('model');
$check['text_space_384']=$model['vector_space']==='text'&&$model['dimensions']===384&&$data['device']==='cpu';
$delta=fn(array$a,array$b)=>max(array_map(fn($v,$i)=>abs($v-$b[$i]),$a,array_keys($a)));
$check['nfc_equivalence']=$delta($data['embeddings'][0]['vector'],$data['embeddings'][1]['vector'])<=1e-6;
$check['unit_norm']=collect($data['embeddings'])->every(fn($row)=>abs(sqrt(array_sum(array_map(fn($v)=>$v*$v,$row['vector'])))-1)<1e-5);
$repeat=$send($body($texts),$token);$http['deterministic_repeat']=$repeat->status();
$check['determinism']=$repeat->status()===200&&$delta($data['embeddings'][0]['vector'],$repeat->json('data.embeddings.0.vector'))<=1e-6;
foreach(['empty_text'=>['   '],'empty_batch'=>[],'too_many'=>array_fill(0,17,'camisa'),'too_many_characters'=>[str_repeat('a',8193)],'too_many_tokens'=>[str_repeat('ropa ',1000)]]as$key=>$values){$r=$send($body($values),$token);$http[$key]=$r->status();$check[$key]=$r->status()===422;}
$r=$send($body([str_repeat('ropa ',1000)],'document'),$token);$http['explicit_document_truncation']=$r->status();$check['explicit_document_truncation']=$r->status()===200&&$r->json('data.embeddings.0.truncated')===true;
$r=Http::withToken($token)->acceptJson()->timeout(90)->withBody(str_repeat('x',65537),'application/json')->post($url);$http['body_limit']=$r->status();$check['body_limit']=$r->status()===413;
$requests=$body(array_fill(0,16,str_repeat('camisa algodón ',35)),'document');$other=$body(['blusa']);
$pool=Http::pool(fn(Pool$pool)=>[$pool->as('batch')->withToken($token)->acceptJson()->timeout(90)->post($url,$requests),$pool->as('query')->withToken($token)->acceptJson()->timeout(90)->post($url,$other)]);
$http['concurrent_batch']=$pool['batch']->status();$http['concurrent_query']=$pool['query']->status();
$check['bounded_concurrency']=in_array($http['concurrent_batch'],[200,503],true)&&in_array($http['concurrent_query'],[200,503],true)&&in_array(200,[$http['concurrent_batch'],$http['concurrent_query']],true);
$report=['schema_version'=>'mati-text-runtime-probe-1','created_at_utc'=>gmdate('c'),'model'=>$model,'http'=>$http,'checks'=>$check,
 'first_response_timing_ms'=>$valid->json('timing_ms'),'wall_timing_ms'=>$timing,'real_vectors_exposed_in_artifact'=>false,'passed'=>!in_array(false,$check,true),'no_500'=>!in_array(500,$http,true),'commercial_mutations'=>false];
file_put_contents($out,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n");chmod($out,0600);echo json_encode($report)."\n";exit($report['passed']?0:1);
