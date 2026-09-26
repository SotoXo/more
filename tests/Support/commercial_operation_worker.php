<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'testing') {
    throw new RuntimeException('Concurrency workers must only use PostgreSQL testing.');
}

[$script, $id, $userId, $variantId] = $argv;
$request = \Illuminate\Http\Request::create('/superadmin/comercial/ventas', 'POST', [
    'operation_id' => $id, 'tiv_ven' => 'fisica', 'mpa_ven' => 'efectivo', 'des_ven' => 0,
    'sale_items' => [['cod_prenda_variantes_dve' => $variantId, 'can_dve' => 1]],
]);
$request->setUserResolver(fn () => \App\Models\User::findOrFail($userId));
$called = false;
$response = app(\App\Http\Middleware\CommercialOperation::class)->handle($request, function ($request) use (&$called) {
    $called = true;
    fwrite(STDOUT, "CLAIMED\n");
    fflush(STDOUT);
    usleep(750000);
    $service = app(\App\Services\Superadmin\SaleService::class);
    $sale = $service->createSale($request, $service->validate($request));

    return redirect('/superadmin/comercial/ventas/'.$sale->id_venta);
});
echo json_encode(['created' => $called, 'location' => $response->headers->get('Location')])."\n";
