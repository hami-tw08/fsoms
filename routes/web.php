<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;

// --- Admin Controllers ---
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\SlotController;

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
Route::controller(ReservationController::class)->group(function () {
    Route::get('/reserve', 'create')->name('reserve.create');          // 画面表示
    Route::post('/reserve', 'store')->name('reserve.store');           // 最終予約POST
    Route::post('/reserve/create-step', 'storeCreateStep')             // 中間保存POST
        ->name('reserve.storeCreateStep');
});

// カレンダーが叩く空き枠AJAX（例：/slots?date=2025-09-28&slot_type=store）
Route::get('/slots', [ReservationController::class, 'slots'])
    ->name('public.slots'); // admin配下と名前が被らないように

/**
 * Authenticated pages
 */
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// 必要ならプロフィールを有効化（今はコメントアウトのままでもOK）
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

        // 管理用の枠一覧/トグル（既存）
        Route::get('/slots', [SlotController::class, 'index'])->name('slots.index');
        Route::post('/slots/{id}/toggle', [SlotController::class, 'toggle'])->name('slots.toggle');
    });

require __DIR__ . '/auth.php';
