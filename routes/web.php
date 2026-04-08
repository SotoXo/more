<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::get('/mujer', function () {
    return Inertia::render('Shop/Collection', [
        'collection' => 'mujer',
    ]);
})->name('shop.women');

Route::get('/hombre', function () {
    return Inertia::render('Shop/Collection', [
        'collection' => 'hombre',
    ]);
})->name('shop.men');

Route::get('/accesorios', function () {
    return Inertia::render('Shop/Collection', [
        'collection' => 'accesorios',
    ]);
})->name('shop.accessories');

Route::get('/novedades', function () {
    return Inertia::render('Shop/Collection', [
        'collection' => 'novedades',
    ]);
})->name('shop.new-in');

Route::get('/colecciones', function () {
    return Inertia::render('Shop/Editorial', [
        'mode' => 'collections',
    ]);
})->name('shop.collections');

Route::get('/editorial', function () {
    return Inertia::render('Shop/Editorial', [
        'mode' => 'editorial',
    ]);
})->name('shop.editorial');

Route::get('/producto/{product}', function (string $product) {
    return Inertia::render('Shop/Product', [
        'productSlug' => $product,
    ]);
})->name('shop.product');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
