<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->toDateString();

        $totalReservations = DB::table('reservations')->count();
        $todayReservations = DB::table('reservations')->whereDate('created_at', $today)->count();
        $activeSlots = DB::table('reservation_slots')->where('is_active', 1)->count();

        return view('admin.dashboard', compact(
            'totalReservations',
            'todayReservations',
            'activeSlots',
            'today'
        ));
    }
}

