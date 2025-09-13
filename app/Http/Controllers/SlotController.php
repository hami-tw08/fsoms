<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ReservationSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SlotController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'date' => ['required','date'],
            'slot_type' => ['required','in:store,delivery'],
        ]);

        $shopId = 1; // 1店舗運用想定。将来 shops を消すなら固定でOK

        // 残数 = capacity - 有効な予約数(booked/completed)
        $rows = ReservationSlot::query()
            ->where('shop_id', $shopId)
            ->where('slot_date', $request->date)
            ->where('slot_type', $request->slot_type)
            ->where('is_active', true)
            ->leftJoin('reservations as r', function($j) {
                $j->on('r.slot_id', 'reservation_slots.id')
                  ->whereIn('r.status', ['booked','completed']);
            })
            ->groupBy('reservation_slots.id','slot_date','start_time','end_time','capacity')
            ->orderBy('start_time')
            ->get([
                'reservation_slots.id',
                'slot_date',
                DB::raw("TIME_FORMAT(start_time, '%H:%i') as start_time"),
                DB::raw("TIME_FORMAT(end_time,   '%H:%i') as end_time"),
                DB::raw('capacity - COUNT(r.id) as remaining'),
            ])
            ->filter(fn($row) => (int)$row->remaining > 0)
            ->values();

        return response()->json($rows);
    }
}
