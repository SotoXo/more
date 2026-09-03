<?php

use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CompraOnlineController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PrendaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\Superadmin\AuditController as SuperadminAuditController;
use App\Http\Controllers\Superadmin\DashboardController as SuperadminDashboardController;
use App\Http\Controllers\Superadmin\DatabaseBackupController as SuperadminDatabaseBackupController;
use App\Http\Controllers\Superadmin\DamagedProductController as SuperadminDamagedProductController;
use App\Http\Controllers\Superadmin\InventoryMovementController as SuperadminInventoryMovementController;
use App\Http\Controllers\Superadmin\LogsController as SuperadminLogsController;
use App\Http\Controllers\Superadmin\ProfileController as SuperadminProfileController;
use App\Http\Controllers\Superadmin\ReportExportController as SuperadminReportExportController;
use App\Http\Controllers\Superadmin\ReportsController as SuperadminReportsController;
use App\Http\Controllers\Superadmin\ResourceController as SuperadminResourceController;
use App\Http\Controllers\Superadmin\SalePickupController as SuperadminSalePickupController;
use App\Http\Controllers\Vendedor\VendedorDamagedProductController;
use App\Http\Controllers\Vendedor\VendedorDashboardController;
use App\Http\Controllers\Vendedor\VendedorPageController;
use App\Http\Controllers\Vendedor\VendedorPaymentReviewController;
use App\Http\Controllers\Vendedor\VendedorPickupController;
use App\Http\Controllers\Vendedor\VendedorReportController;
use App\Http\Controllers\Vendedor\VendedorReservationController;
use App\Http\Controllers\Vendedor\VendedorSalesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Landing ────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');

// ─── Catálogo público ───────────────────────────────────────
Route::get('/mujer',      [CatalogoController::class, 'mujer'])->name('shop.women');
Route::get('/hombre',     [CatalogoController::class, 'hombre'])->name('shop.men');
Route::get('/novedades',  [CatalogoController::class, 'novedades'])->name('shop.new-in');
Route::get('/accesorios', [CatalogoController::class, 'accesorios'])->name('shop.accessories');
Route::get('/catalogo',   [CatalogoController::class, 'catalogo'])->name('shop.catalog');

// Producto detalle – parámetro = id_prenda
Route::get('/producto/{id}', [PrendaController::class, 'show'])
    ->where('id', '[0-9]+')
    ->name('shop.product');

// Editorial / colecciones
Route::get('/colecciones', [CatalogoController::class, 'colecciones'])->name('shop.collections');
Route::get('/editorial',   [CatalogoController::class, 'editorial'])->name('shop.editorial');

// ─── Carrito (sin sesión requerida) ─────────────────────────
Route::get('/bolsa', [CarritoController::class, 'index'])->name('carrito.index');
Route::middleware('auth')->post('/bolsa/confirmar', [CarritoController::class, 'confirmar'])->name('carrito.confirmar');
Route::middleware('auth')->post('/compras/online', [CompraOnlineController::class, 'store'])->name('compras.online.store');
Route::get('/carrito', fn () => Inertia::render('CarritoCompra'))->name('compras.carrito');
Route::middleware('auth')->get('/checkout', [CompraOnlineController::class, 'create'])->name('compras.checkout');

Route::prefix('api/carrito')->group(function () {
    Route::post('/agregar',        [CarritoController::class, 'agregar'])->name('carrito.agregar');
    Route::patch('/{varianteId}',  [CarritoController::class, 'actualizar'])->name('carrito.actualizar');
    Route::delete('/{varianteId}', [CarritoController::class, 'eliminar'])->name('carrito.eliminar');
    Route::delete('/',             [CarritoController::class, 'vaciar'])->name('carrito.vaciar');
});

// ─── Favoritos (requiere sesión) ────────────────────────────
Route::middleware('auth')->post('/api/favoritos/toggle', [FavoritoController::class, 'toggle'])->name('favoritos.toggle');

// ─── Cuenta del usuario ──────────────────────────────────────
Route::middleware('auth')->prefix('cuenta')->name('cuenta.')->group(function () {
    Route::get('/',          [CuentaController::class, 'index'])->name('index');
    Route::get('/pedidos',   [CuentaController::class, 'pedidos'])->name('pedidos');
    Route::get('/reservas',  [CuentaController::class, 'reservas'])->name('reservas');
    Route::get('/reservas/nueva', fn () => redirect()->route('carrito.index'))->name('reservas.nueva');
    Route::get('/favoritos', [FavoritoController::class, 'index'])->name('favoritos');
});

Route::middleware('auth')->get('/mis-reservas/{reserva}/recibo', [ReceiptController::class, 'customerReservation'])
    ->name('cuenta.reservas.recibo');
Route::middleware('auth')->get('/mis-compras/{venta}/recibo', [ReceiptController::class, 'customerSale'])
    ->name('cuenta.pedidos.recibo');

Route::middleware('auth')->prefix('notificaciones')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/resumen', [NotificationController::class, 'summary'])->name('summary');
    Route::post('/{id}/leer', [NotificationController::class, 'markAsRead'])->name('mark-read');
    Route::post('/leer-todas', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
});

Route::middleware(['auth', 'vendedor'])->prefix('vendedor')->name('vendedor.')->group(function () {
    Route::get('/', [VendedorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/prendas', [VendedorPageController::class, 'prendas'])->name('prendas.index');
    Route::get('/prendas/{prenda}', [VendedorPageController::class, 'prendaShow'])->whereNumber('prenda')->name('prendas.show');
    Route::get('/existencias', [VendedorPageController::class, 'existencias'])->name('existencias.index');
    Route::get('/alertas-stock', [VendedorPageController::class, 'alertasStock'])->name('alertas-stock.index');
    Route::get('/ventas', [VendedorSalesController::class, 'index'])->name('ventas.index');
    Route::get('/ventas/crear', [VendedorSalesController::class, 'create'])->name('ventas.create');
    Route::post('/ventas', [VendedorSalesController::class, 'store'])->name('ventas.store');
    Route::get('/ventas/{venta}', [VendedorSalesController::class, 'show'])->whereNumber('venta')->name('ventas.show');
    Route::get('/reservas', [VendedorReservationController::class, 'index'])->name('reservas.index');
    Route::get('/reservas/crear', [VendedorReservationController::class, 'create'])->name('reservas.create');
    Route::post('/reservas', [VendedorReservationController::class, 'store'])->name('reservas.store');
    Route::get('/reservas/{reserva}', [VendedorReservationController::class, 'show'])->whereNumber('reserva')->name('reservas.show');
    Route::post('/reservas/{reserva}/pagos', [VendedorReservationController::class, 'storePayment'])->whereNumber('reserva')->name('reservas.payments.store');
    Route::post('/reservas/{reserva}/cancelar', [VendedorReservationController::class, 'cancel'])->whereNumber('reserva')->name('reservas.cancel');
    Route::get('/pagos', [VendedorPaymentReviewController::class, 'index'])->name('pagos.index');
    Route::get('/pagos/{pago}', [VendedorPaymentReviewController::class, 'show'])->where('pago', '(venta|reserva)-[0-9]+')->name('pagos.show');
    Route::post('/pagos/{pago}/aprobar', [VendedorPaymentReviewController::class, 'approve'])->where('pago', '(venta|reserva)-[0-9]+')->name('pagos.approve');
    Route::post('/pagos/{pago}/rechazar', [VendedorPaymentReviewController::class, 'reject'])->where('pago', '(venta|reserva)-[0-9]+')->name('pagos.reject');
    Route::get('/recojos', [VendedorPickupController::class, 'index'])->name('recojos.index');
    Route::get('/recojos/ventas/{venta}', [VendedorPickupController::class, 'sale'])->whereNumber('venta')->name('recojos.ventas.show');
    Route::post('/recojos/ventas/{venta}/entregar', [VendedorPickupController::class, 'deliverSale'])->whereNumber('venta')->name('recojos.ventas.deliver');
    Route::get('/recojos/reservas/{reserva}', [VendedorPickupController::class, 'reservation'])->whereNumber('reserva')->name('recojos.reservas.show');
    Route::post('/recojos/reservas/{reserva}/entregar', [VendedorPickupController::class, 'deliverReservation'])->whereNumber('reserva')->name('recojos.reservas.deliver');
    Route::get('/prendas-daniadas', [VendedorDamagedProductController::class, 'index'])->name('prendas-daniadas.index');
    Route::get('/prendas-daniadas/crear', [VendedorDamagedProductController::class, 'create'])->name('prendas-daniadas.create');
    Route::post('/prendas-daniadas', [VendedorDamagedProductController::class, 'store'])->name('prendas-daniadas.store');
    Route::get('/prendas-daniadas/{reporte}', [VendedorDamagedProductController::class, 'show'])->whereNumber('reporte')->name('prendas-daniadas.show');
    Route::get('/reportes', [VendedorReportController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [VendedorReportController::class, 'export'])->name('reportes.export');
    Route::get('/reportes/imprimir', [VendedorReportController::class, 'print'])->name('reportes.print');
});

Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', [SuperadminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/perfil', [SuperadminProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [SuperadminProfileController::class, 'update'])->name('profile.update');
    Route::put('/perfil/password', [SuperadminProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/reportes/exportar/{type}.csv', [SuperadminReportExportController::class, 'csv'])
        ->whereIn('type', ['ventas', 'reservas', 'pagos', 'inventario', 'clientes'])
        ->name('reports.export.csv');
    Route::get('/reportes/imprimir/{type}', [SuperadminReportExportController::class, 'print'])
        ->whereIn('type', ['ventas', 'reservas', 'pagos', 'inventario', 'clientes'])
        ->name('reports.print');
    Route::get('/reportes', [SuperadminReportsController::class, 'index'])->name('reports');
    Route::get('/logs', [SuperadminLogsController::class, 'index'])->name('logs');
    Route::get('/logs/{log}', [SuperadminLogsController::class, 'show'])->whereNumber('log')->name('logs.show');
    Route::get('/auditoria', [SuperadminAuditController::class, 'index'])->name('audit');
    Route::get('/auditoria/{auditLog}', [SuperadminAuditController::class, 'show'])->whereNumber('auditLog')->name('audit.show');
    Route::get('/backups', [SuperadminDatabaseBackupController::class, 'index'])->name('backups');
    Route::post('/backups/generar', [SuperadminDatabaseBackupController::class, 'store'])->name('backups.store');
    Route::get('/backups/{backup}/descargar', [SuperadminDatabaseBackupController::class, 'download'])->whereNumber('backup')->name('backups.download');
    Route::get('/inventario/movimientos/exportar.csv', [SuperadminInventoryMovementController::class, 'exportCsv'])->name('inventory-movements.export.csv');
    Route::get('/inventario/movimientos', [SuperadminInventoryMovementController::class, 'index'])->name('inventory-movements.index');
    Route::get('/inventario/movimientos/{movement}', [SuperadminInventoryMovementController::class, 'show'])->whereNumber('movement')->name('inventory-movements.show');
    Route::get('/prendas-daniadas', [SuperadminDamagedProductController::class, 'index'])->name('damaged-products.index');
    Route::get('/prendas-daniadas/crear', [SuperadminDamagedProductController::class, 'create'])->name('damaged-products.create');
    Route::post('/prendas-daniadas', [SuperadminDamagedProductController::class, 'store'])->name('damaged-products.store');
    Route::get('/prendas-daniadas/{reporte}', [SuperadminDamagedProductController::class, 'show'])->whereNumber('reporte')->name('damaged-products.show');
    Route::post('/prendas-daniadas/{reporte}/en-revision', [SuperadminDamagedProductController::class, 'markInReview'])->whereNumber('reporte')->name('damaged-products.in-review');
    Route::post('/prendas-daniadas/{reporte}/confirmar', [SuperadminDamagedProductController::class, 'confirm'])->whereNumber('reporte')->name('damaged-products.confirm');
    Route::post('/prendas-daniadas/{reporte}/descartar', [SuperadminDamagedProductController::class, 'discard'])->whereNumber('reporte')->name('damaged-products.discard');
    Route::post('/prendas-daniadas/{reporte}/reparar', [SuperadminDamagedProductController::class, 'repair'])->whereNumber('reporte')->name('damaged-products.repair');
    Route::post('/prendas-daniadas/{reporte}/baja-definitiva', [SuperadminDamagedProductController::class, 'definitiveLoss'])->whereNumber('reporte')->name('damaged-products.definitive-loss');
    Route::post('/prendas-daniadas/{reporte}/resolver', [SuperadminDamagedProductController::class, 'resolve'])->whereNumber('reporte')->name('damaged-products.resolve');
    Route::get('/comercial/reservas/{reserva}/recibo', [ReceiptController::class, 'adminReservation'])->name('receipts.reservations.show');
    Route::get('/comercial/ventas/{venta}/recibo', [ReceiptController::class, 'adminSale'])->name('receipts.sales.show');
    Route::post('/comercial/ventas/{venta}/lista-recojo', [SuperadminSalePickupController::class, 'markReady'])->name('sales.pickup.ready');
    Route::post('/comercial/ventas/{venta}/entregar', [SuperadminSalePickupController::class, 'markDelivered'])->name('sales.pickup.deliver');
    Route::post('/{section}/{resource}/inline', [SuperadminResourceController::class, 'storeInline'])->name('resources.inline');
    Route::get('/{section}/{resource}', [SuperadminResourceController::class, 'index'])->name('resources.index');
    Route::get('/{section}/{resource}/nuevo', [SuperadminResourceController::class, 'create'])->name('resources.create');
    Route::post('/{section}/{resource}', [SuperadminResourceController::class, 'store'])->name('resources.store');
    Route::get('/{section}/{resource}/{record}/editar', [SuperadminResourceController::class, 'edit'])->name('resources.edit');
    Route::match(['put', 'patch'], '/{section}/{resource}/{record}', [SuperadminResourceController::class, 'update'])->name('resources.update');
    Route::delete('/{section}/{resource}/{record}', [SuperadminResourceController::class, 'destroy'])->name('resources.destroy');
    Route::get('/{section}/{resource}/{record}', [SuperadminResourceController::class, 'show'])->name('resources.show');
});

// ─── Perfil (Breeze) ────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Dashboard legacy → redirige a cuenta
Route::get('/dashboard', function (Request $request) {
    $user = $request->user();

    if ($user && method_exists($user, 'isSuperadmin')) {
        $user->loadMissing('rol');

        if ($user->isSuperadmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        if (method_exists($user, 'isVendedor') && $user->isVendedor()) {
            return redirect()->route('vendedor.dashboard');
        }
    }

    return redirect()->route('cuenta.index');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__ . '/auth.php';
