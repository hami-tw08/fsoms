<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SlotController;

// --- Admin Controllers ---
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminReservationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/**
 * Public
 */
// トップは予約画面へ
Route::redirect('/', '/reserve');

// 予約（画面表示／最終POST／中間POST: 日付・方法・時間をセッション保存）
Route::get('/reserve', [ReservationController::class, 'create'])->name('reserve.create');   // 画面表示
Route::post('/reserve', [ReservationController::class, 'store'])->name('reserve.store');    // 最終予約POST
Route::post('/reserve/create-step', [ReservationController::class, 'storeCreateStep'])
    ->name('reserve.storeCreateStep'); // 中間保存POST（時間選択後にここへPOST）


// ▼ カレンダーが叩く空き枠AJAX（例：/slots?date=2025-09-28&slot_type=store|delivery）
//   Public側の /slots は ReservationController@slots のみに統一（JSON返却想定）
Route::get('/slots', [ReservationController::class, 'slots'])->name('public.slots');

// ▼ 商品（時間確定後に遷移）
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');


/**
 * Authenticated pages
 */
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 必要ならプロフィールを有効化（今はコメントアウトでもOK）
// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });


/*
|--------------------------------------------------------------------------
| Admin Routes (/admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'is_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');

        // 管理用の枠一覧/トグル（SlotControllerは Admin配下のURLに限定）
        Route::get('/slots', [SlotController::class, 'index'])->name('slots.index');
        Route::post('/slots/{id}/toggle', [SlotController::class, 'toggle'])->name('slots.toggle');
    });

require __DIR__ . '/auth.php';
