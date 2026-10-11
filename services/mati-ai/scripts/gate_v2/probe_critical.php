<?php
declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use App\Services\AI\RagGateV2Evidence;
use App\Services\AI\RagGateV2Router;
use App\Services\AI\RagGateV2Model;
require dirname(__DIR__,4).'/vendor/autoload.php';
$app=require dirname(__DIR__,4).'/bootstrap/app.php';$app->make(Kernel::class)->bootstrap();
if(!in_array('--sealed-candidates-only',$argv,true))throw new RuntimeException('EXPLICIT_SEALED_EVALUATION_REQUIRED');
$root=storage_path('app/ai-rag-gate-v2');
$out=$root.'/critical-results.json';if(file_exists($out))throw new RuntimeException('EVIDENCE_ALREADY_EXISTS');
$selection=json_decode(file_get_contents($root.'/sealed-selection.json'),true,64,JSON_THROW_ON_ERROR);
$suite=json_decode(file_get_contents($root.'/critical-suite.json'),true,64,JSON_THROW_ON_ERROR);
if(($selection['schema_version']??null)!=='mati-rag-gate-sealed-selection-2'||count($selection['representatives']??[])!==3)throw new RuntimeException('SEALED_SELECTION_REQUIRED');
$evidence=app(RagGateV2Evidence::class);$router=app(RagGateV2Router::class);$gate=app(RagGateV2Model::class);
$context=$evidence->context();$expectedContext=$selection['context'];ksort($context);ksort($expectedContext);if($context!==$expectedContext)throw new RuntimeException('SEALED_CONTEXT_MISMATCH');
$models=[];$reports=[];
foreach($selection['representatives'] as $rep){
 $file=$root.'/models/'.basename($rep['model_path']);
 if(hash_file('sha256',$file)!==$rep['model_sha256'])throw new RuntimeException('SEALED_MODEL_MISMATCH');
 $model=json_decode(file_get_contents($file),true,64,JSON_THROW_ON_ERROR);$gate->validate($model,$context);
 $models[$rep['candidate_id']]=$model;
 $reports[$rep['candidate_id']]=['candidate_id'=>$rep['candidate_id'],'model_sha256'=>$rep['model_sha256'],'tool_cases_n'=>0,'tool_bypass_count'=>0,'tool_routes_correct_n'=>0,'camisa_blusa_accepted'=>false,'camisa_blusa_all3'=>false,'cases'=>[]];
}
DB::beginTransaction();DB::statement('SET TRANSACTION READ ONLY');
try{
foreach($suite['queries'] as $case){
 $start=microtime(true);$routing=$router->route($case['query']);$result=$evidence->extract($case['query']);
 $currentContext=$result['context'];ksort($currentContext);if($currentContext!==$context)throw new RuntimeException('SEALED_CONTEXT_MISMATCH');
 $prototypeRetrievalMs=(microtime(true)-$start)*1000;
 foreach($models as $id=>$model){
  $gateStart=microtime(true);$binary=$gate->evaluate($model,$result['features'],count($result['vector_top3']),$context);$gateMs=(microtime(true)-$gateStart)*1000;
  $decision=match($routing['route']){'tool'=>'TOOL_REQUIRED','required_information'=>'INSUFFICIENT_EVIDENCE','candidate_rag'=>$binary['decision']==='answerable'?'RAG_ANSWERABLE':'INSUFFICIENT_EVIDENCE',default=>throw new RuntimeException('ROUTING_INVALID')};
  $r=&$reports[$id];
  if($case['ground_truth_class']==='TOOL_REQUIRED'){$r['tool_cases_n']++;$r['tool_bypass_count']+=(int)($decision==='RAG_ANSWERABLE');$r['tool_routes_correct_n']+=(int)($decision==='TOOL_REQUIRED');}
  if($case['id']==='critical-camisa'){$r['camisa_blusa_accepted']=$decision==='RAG_ANSWERABLE';$r['camisa_blusa_all3']=count(array_diff($case['expected_document_ids'],array_column($result['vector_top3'],'document_key')))===0;}
  $r['cases'][]=['id'=>$case['id'],'query'=>$case['query'],'ground_truth_class'=>$case['ground_truth_class'],'decision'=>$decision,'router'=>$routing,'answerability'=>$binary,'features'=>$result['features'],'vector_top3'=>array_map(fn($row)=>['document_key'=>$row['document_key'],'chunk_key'=>$row['chunk_key'],'similarity'=>$row['similarity']],$result['vector_top3']),'lexical_top3'=>array_map(fn($row)=>['document_key'=>$row['document_key'],'lexical_score'=>$row['lexical_score']],$result['lexical_top3']),'timing'=>$result['timing']+['php_gate_ms'=>$gateMs,'prototype_total_with_gate_ms'=>$prototypeRetrievalMs+$gateMs],'chunks_released_publicly'=>false];
  unset($r);
 }
}
DB::rollBack();
$report=['schema_version'=>'mati-rag-gate-critical-results-2','protocol_sha256'=>$selection['protocol_sha256'],'timestamp'=>gmdate('c'),'context'=>$context,'sealed_selection_sha256'=>hash_file('sha256',$root.'/sealed-selection.json'),'critical_suite_sha256'=>hash_file('sha256',$root.'/critical-suite.json'),'source_sha256'=>hash_file('sha256',__FILE__),'representatives'=>array_values($reports),'read_only'=>true,'commercial_mutations'=>false,'public_gate_bypass'=>false,'private_prototype_evaluation'=>true,'holdout_used'=>false];
file_put_contents($out,json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n");chmod($out,0600);
echo json_encode(['critical_cases'=>count($suite['queries']),'representatives'=>array_map(fn($r)=>array_diff_key($r,['cases'=>1]),array_values($reports)),'no_commercial_mutations'=>true],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
}catch(Throwable $error){DB::rollBack();throw $error;}
