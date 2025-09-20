<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SlotController extends Controller
{
    public function index(Request $request)
    {
        $q = DB::table('reservation_slots');

        if ($type = $request->get('type')) {
            $q->where('slot_type', $type); // 'store' or 'delivery'
        }
        if ($date = $request->get('date')) {
            $q->whereDate('slot_date', $date);
        }

        $slots = $q->orderBy('slot_date')->orderBy('start_time')->paginate(100)->withQueryString();

        return view('admin.slots.index', compact('slots'));
    }

    public function toggle(int $id): RedirectResponse
    {
        $slot = DB::table('reservation_slots')->where('id', $id)->first();
        if (!$slot) return back()->with('status', '枠が見つかりません');

        DB::table('reservation_slots')
            ->where('id', $id)
            ->update(['is_active' => $slot->is_active ? 0 : 1, 'updated_at' => now()]);

        return back()->with('status', '更新しました');
    }
}