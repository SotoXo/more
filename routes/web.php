<?php

use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrendaController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/favoritos', [FavoritoController::class, 'index'])->name('favoritos');
});

// ─── Perfil (Breeze) ────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Dashboard legacy → redirige a cuenta
Route::get('/dashboard', fn () => redirect()->route('cuenta.index'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__ . '/auth.php';
