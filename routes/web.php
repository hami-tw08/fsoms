<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SlotController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// === 予約関連 ===
// ゲストでもアクセス可能にする
Route::get('/reserve', [ReservationController::class, 'create'])->name('reserve.create');
Route::post('/reserve', [ReservationController::class, 'store'])->name('reserve.store');
Route::post('/reserve/meta', [ReservationController::class, 'storeCreateStep'])->name('reserve.storeCreateStep');

// 空き枠一覧（?date=YYYY-MM-DD&slot_type=store|delivery）
Route::get('/slots', [SlotController::class, 'index'])->name('slots.index');

// 商品
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// 管理（店舗）側：必要なら auth ミドルウェアを付与して
Route::middleware([])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});

// カート
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');             // 商品詳細から追加
Route::patch('/cart/{rowId}', [CartController::class, 'update'])->name('cart.update');  // 数量変更
Route::delete('/cart/{rowId}', [CartController::class, 'remove'])->name('cart.remove'); // 行削除
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');           // 全クリア
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');              // 予約商品一覧

// Checkout: 配送先情報
Route::get('/checkout/shipping', [CheckoutController::class, 'shipping'])->name('checkout.shipping');
Route::post('/checkout/shipping', [CheckoutController::class, 'storeShipping'])->name('checkout.shipping.store');

// Checkout: 最終確認・確定・完了
Route::get('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
Route::post('/checkout/place', [CheckoutController::class, 'place'])->name('checkout.place');
Route::get('/checkout/complete', [CheckoutController::class, 'complete'])->name('checkout.complete');

require __DIR__.'/auth.php';