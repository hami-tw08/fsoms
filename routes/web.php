<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;

// --- Admin Controllers ---
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\SlotController as AdminSlotController; // ★ 追加
use App\Http\Middleware\IsAdmin;

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
// 互換：昔の 'reservations.create' を 'reserve.create' へ301で流す
Route::get('/reservations/create', fn () => redirect()->route('reserve.create'), 301)
    ->name('reservations.create');

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

// ★ 完了画面から新規開始（セッション初期化 → /reserve へ）
Route::post('/reserve/reset', [ReservationController::class, 'reset'])->name('reserve.reset');

/** 認証後ページ */
Route::get('/dashboard', fn () => view('dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

/** Admin */
Route::middleware(['auth', IsAdmin::class])
    ->prefix('admin')->name('admin.')
    ->group(function () {

        // ダッシュボード
        Route::get('/', DashboardController::class)->name('dashboard');

        // 予約一覧
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/export', [AdminReservationController::class, 'export'])->name('reservations.export');
        Route::get('/reservations/{reservation}', [AdminReservationController::class, 'show'])->name('reservations.show');

        // ▼▼▼ ここから追記：削除系 ▼▼▼
        // 個別削除（1件）
        Route::delete('/reservations/{reservation}', [AdminReservationController::class, 'destroy'])
            ->name('reservations.destroy');

        // 選択削除（複数IDをカンマ区切りで受け取る）
        Route::delete('/reservations', [AdminReservationController::class, 'destroySelected'])
            ->name('reservations.destroySelected');

        // 全件削除（超危険操作）
        Route::delete('/reservations-all', [AdminReservationController::class, 'destroyAll'])
            ->name('reservations.destroyAll');
        // ▲▲▲ 追記ここまで ▲▲▲

        // ▼ スロット管理（新UI：通知閾値／収容数の一括更新）
        Route::get('/slots', [AdminSlotController::class, 'index'])->name('slots.index');             // 一覧＆編集フォーム
        Route::post('/slots/bulk-update', [AdminSlotController::class, 'bulkUpdate'])->name('slots.bulk-update'); // 一括更新

        // ▼ 今すぐ再生成（5か月先まで）※管理画面の手動ボタン用
        Route::post('/slots/generate-now', function () {
            \Artisan::call('slots:generate-monthly', [
                '--months' => 5,
                '--shop' => 1,
                '--from-first-of-month' => true,
            ]);
            return back()->with('status', '5か月先までの枠を再生成しました');
        })->name('slots.generate-now');

        // 商品管理
        Route::resource('products', AdminProductController::class)->except(['show']);
    });

require __DIR__ . '/auth.php';

