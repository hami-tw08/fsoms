<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AdminReservationController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('reservations');

        if ($kw = $request->get('q')) {
            $q->where(function($w) use ($kw) {
                $w->orWhere('guest_name', 'like', "%{$kw}%")
                  ->orWhere('guest_phone', 'like', "%{$kw}%")
                  ->orWhere('notes', 'like', "%{$kw}%");
            });
        }

        $reservations = $q->orderByDesc('id')->paginate(50)->withQueryString();

        return view('admin.reservations.index', compact('reservations'));
    }
}