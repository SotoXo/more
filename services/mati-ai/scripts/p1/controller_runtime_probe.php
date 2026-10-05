<?php
// Direct controller integration with REAL private HTTP/DB. No web session/middleware/browser certification.
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use App\Http\Controllers\Superadmin\AiController;
use App\Models\User;
use App\Services\AI\CatalogEmbeddingStore;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
$connection=p1Connection();
$actor=User::with('rol')->get()->first(fn($user)=>$user->isSuperadmin());
if(!$actor)throw new RuntimeException('NO_LOCAL_SUPERADMIN');
$controller=app(AiController::class);$results=[];
$files=['analyze'=>'/var/www/html/storage/app/public/productos/11/jersey-alpaca-frueyyc3.jpg','barcode'=>'/var/www/html/storage/app/ai-p1/barcode-ean13.png','label'=>'/var/www/html/storage/app/ai-p1/label-synthetic.png','similar'=>'/var/www/html/storage/app/public/productos/11/jersey-alpaca-frueyyc3.jpg'];
DB::beginTransaction();
try {
 foreach($files as $operation=>$path){
  foreach(['upload','existing','both','neither','unknown_id','invalid_image'] as $case){
   $parameters=in_array($case,['existing','both'],true)?['existing_image_id'=>13]:($case==='unknown_id'?['existing_image_id'=>999999999]:[]);
   $request=Request::create('/superadmin/ai/'.$operation,'POST',$parameters,[],[],['HTTP_ACCEPT'=>'application/json']);
   $request->setUserResolver(fn()=>$actor);
   if(in_array($case,['upload','both'],true))$request->files->set('image',new UploadedFile($path,basename($path),mime_content_type($path),null,true));
   if($case==='invalid_image')$request->files->set('image',new UploadedFile('/var/www/html/composer.json','invalid.txt','text/plain',null,true));
   $start=microtime(true);
   try{$response=$operation==='similar'?$controller->similar($request,app(CatalogEmbeddingStore::class)):$controller->image($request,$operation);$status=$response->getStatusCode();$body=$response->getData(true);}
   catch(ValidationException $exception){$status=422;$body=['errors'=>array_keys($exception->errors())];}
   $expected=in_array($case,['upload','existing'],true)?200:422;
   $results[]=['operation'=>$operation,'case'=>$case,'status_code'=>$status,'expected'=>$expected,'passed'=>$status===$expected&&($expected===422||!isset($body['error'])),'wall_ms'=>(microtime(true)-$start)*1000,'body'=>$body];
  }
 }
}finally{DB::rollBack();}
$report=['scope'=>'Direct Laravel controller validation -> actual HTTP FastAPI -> actual PostgreSQL, AI writes transaction rolled back. Not web middleware/auth/browser E2E.','connection'=>$connection,'mock_http'=>false,'commercial_product_writes'=>false,'cases'=>$results,'passed'=>!in_array(false,array_column($results,'passed'),true)];
p1Output('controller-runtime-probe',$report);
echo json_encode(['passed'=>$report['passed'],'case_count'=>count($results),'results'=>array_map(fn($r)=>['operation'=>$r['operation'],'case'=>$r['case'],'status'=>$r['status_code'],'passed'=>$r['passed']],$results)],JSON_PRETTY_PRINT).PHP_EOL;
