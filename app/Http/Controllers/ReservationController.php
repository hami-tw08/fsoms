<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Product;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function create(Request $request)
    {
        // 表示する月（?month=YYYY-MM）
        $month = $request->query('month');
        $firstDay = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : Carbon::today()->startOfMonth();

        $start = $firstDay->copy()->startOfWeek(Carbon::MONDAY);
        $end   = $firstDay->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // === 月内の空き枠残数（店頭/配達）を集計 ===
        $monthStart = $firstDay->copy()->startOfMonth()->toDateString();
        $monthEnd   = $firstDay->copy()->endOfMonth()->toDateString();
        $shopId = 1; // 1店舗運用想定

        // capacity 合計 - 予約件数(booked/completed) を日付&slot_typeで集計
        $rows = DB::table('reservation_slots as s')
            ->leftJoin('reservations as r', function ($j) {
                $j->on('r.slot_id', '=', 's.id')
                  ->whereIn('r.status', ['booked','completed']);
            })
            ->where('s.shop_id', $shopId)
            ->whereBetween('s.slot_date', [$monthStart, $monthEnd])
            ->where('s.is_active', true)
            ->groupBy('s.slot_date','s.slot_type')
            ->get([
                's.slot_date',
                's.slot_type',
                DB::raw('SUM(s.capacity) as total_capacity'),
                DB::raw('COUNT(r.id) as total_booked'),
            ]);

        // stats['YYYY-MM-DD']['store'|'delivery'] = 残り数
        $stats = [];
        foreach ($rows as $row) {
            $remaining = (int)$row->total_capacity - (int)$row->total_booked;
            if (!isset($stats[$row->slot_date])) {
                $stats[$row->slot_date] = ['store' => 0, 'delivery' => 0];
            }
            $stats[$row->slot_date][$row->slot_type] = max(0, $remaining);
        }

        // カレンダー配列（週ごと×7日）
        $weeks = [];
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateStr = $cursor->toDateString();
                $week[] = [
                    'date'         => $dateStr,
                    'day'          => $cursor->day,
                    'in_month'     => $cursor->month === $firstDay->month,
                    'is_today'     => $cursor->isToday(),
                    'remain_store' => $stats[$dateStr]['store']    ?? 0,
                    'remain_deliv' => $stats[$dateStr]['delivery'] ?? 0,
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        $prevMonth = $firstDay->copy()->subMonth()->format('Y-m');
        $nextMonth = $firstDay->copy()->addMonth()->format('Y-m');

        return view('reserve.create', compact('weeks', 'firstDay', 'prevMonth', 'nextMonth'));
    }

    // 予約の登録
    public function store(StoreReservationRequest $request)
    {
        $slot = ReservationSlot::findOrFail($request->slot_id);
        $product = Product::findOrFail($request->product_id);
        $total = $product->price * (int)$request->quantity;

        Reservation::create([
            'slot_id'      => $slot->id,
            'user_id'      => optional($request->user())->id, // ゲストなら null
            'product_id'   => $product->id,
            'quantity'     => $request->quantity,
            'total_amount' => $total,
            'status'       => 'booked',
            'notes'        => $request->notes,
            // 配達枠のときだけ入力される想定（バリデーションで制御）
            'delivery_area'        => $request->delivery_area,
            'delivery_postal_code' => $request->delivery_postal_code,
            'delivery_address'     => $request->delivery_address,
            // ゲスト用の入力を使うなら（カラムを作っている場合のみ）
            'guest_name'  => $request->guest_name,
            'guest_phone' => $request->guest_phone,
        ]);

        return redirect()->route('reserve.create', ['month' => $request->query('month')])
                         ->with('status', '予約を作成しました！');
    }

    public function storeCreateStep(Request $request)
    {
        $validated = $request->validate([
            'receive_method' => ['required','in:store,delivery'], // 店頭=store 配送=delivery
            'receive_date'   => ['required','date'],
            'receive_time'   => ['required'], // "10:00" 等
        ]);

        session()->put('reservation.meta', [
            'method' => $validated['receive_method'],
            'date'   => $validated['receive_date'],
            'time'   => $validated['receive_time'],
        ]);

        // 商品選択画面へ（/products?date=... など既存のクエリがあれば付与してもOK）
        return redirect()->route('products.index');
    }

}
