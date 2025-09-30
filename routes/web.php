<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;

// --- Admin Controllers ---
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/** Public */
// トップ→予約トップ
Route::redirect('/', '/reserve');

// 予約フロー
Route::get('/reserve', [ReservationController::class, 'create'])->name('reserve.create');
Route::post('/reserve', [ReservationController::class, 'store'])->name('reserve.store');
Route::post('/reserve/create-step', [ReservationController::class, 'storeCreateStep'])->name('reserve.storeCreateStep');

// 空き枠API
Route::get('/slots', [ReservationController::class, 'slots'])->name('public.slots');

// 商品（slug運用）
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])
    ->where('product', '[A-Za-z0-9\-_]+')
    ->name('products.show');

// レガシーIDURLの救済（任意）
Route::get('/products/{id}', function (int $id) {
    $p = \App\Models\Product::findOrFail($id);
    return redirect()->route('products.show', ['product' => $p->slug], 301);
})->whereNumber('id');

// === 予約一覧（cart） ===
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');              // 一覧表示
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');             // 商品追加（show.bladeからPOST）
Route::patch('/cart/{rowId}', [CartController::class, 'update'])->name('cart.update');  // 数量更新
Route::delete('/cart/{rowId}', [CartController::class, 'remove'])->name('cart.remove'); // 削除
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');           // 全削除

// === チェックアウト ===
// 配送先情報（入力）
Route::get('/checkout/shipping', [CheckoutController::class, 'shipping'])->name('checkout.shipping');
// 配送先情報の保存（shipping.blade.php の form 送信先）
Route::post('/checkout/shipping', [CheckoutController::class, 'storeShipping'])->name('checkout.shipping.store');

// 最終確認（confirm.blade.php へ）
Route::get('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');

// 確定（POST）
Route::post('/checkout/place', [CheckoutController::class, 'place'])->name('checkout.place');

// 完了画面
Route::get('/checkout/complete', [CheckoutController::class, 'complete'])->name('checkout.complete');

/** 認証後ページ */
Route::get('/dashboard', fn () => view('dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

/** Admin */
Route::middleware(['auth', 'is_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');

        Route::get('/slots', [\App\Http\Controllers\SlotController::class, 'index'])->name('slots.index');
        Route::post('/slots/{id}/toggle', [\App\Http\Controllers\SlotController::class, 'toggle'])->name('slots.toggle');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn() => redirect()->route('admin.products.index'))->name('dashboard');
    Route::resource('products', AdminProductController::class)->except(['show']);
    });

require __DIR__ . '/auth.php';
