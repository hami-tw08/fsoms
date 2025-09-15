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
        // 3日前ルール: 今日から3日後を最短予約日とする（例: 9/15なら 9/18 以降）
        $minDate = Carbon::today()->addDays(3);

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
        // さらに "3日前未満" は集計対象から除外（= 在庫0として扱われUI上選べなくなる）
        $rows = DB::table('reservation_slots as s')
            ->leftJoin('reservations as r', function ($j) {
                $j->on('r.slot_id', '=', 's.id')
                  ->whereIn('r.status', ['booked','completed']);
            })
            ->where('s.shop_id', $shopId)
            ->whereBetween('s.slot_date', [$monthStart, $monthEnd])
            ->whereDate('s.slot_date', '>=', $minDate->toDateString()) // ★ 3日前未満を除外
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

                // 3日前未満フラグ（Bladeでボタン無効化やスタイル変更に利用可能）
                $beforeMin = $cursor->lt($minDate);

                $week[] = [
                    'date'         => $dateStr,
                    'day'          => $cursor->day,
                    'in_month'     => $cursor->month === $firstDay->month,
                    'is_today'     => $cursor->isToday(),
                    'before_min'   => $beforeMin, // ★ 追加
                    // 集計結果は3日前未満を除外しているので、3日前未満は自動的に 0 となる
                    'remain_store' => $stats[$dateStr]['store']    ?? 0,
                    'remain_deliv' => $stats[$dateStr]['delivery'] ?? 0,
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        $prevMonth = $firstDay->copy()->subMonth()->format('Y-m');
        $nextMonth = $firstDay->copy()->addMonth()->format('Y-m');

        // ★ minDate をビューへ（ガイダンス表示やJSガードに使用可）
        return view('reserve.create', [
            'weeks'     => $weeks,
            'firstDay'  => $firstDay,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'minDate'   => $minDate,
        ]);
    }

    // 予約の登録
    public function store(StoreReservationRequest $request)
    {
        $slot = ReservationSlot::findOrFail($request->slot_id);

        // ★ サーバ側ガード：スロット日付が today+3 未満なら弾く
        $minDate = Carbon::today()->addDays(3)->toDateString();
        if (Carbon::parse($slot->slot_date)->lt($minDate)) {
            return back()
                ->withErrors(['slot_id' => '予約日は ' . Carbon::parse($minDate)->isoFormat('M月D日(ddd)') . ' 以降を選んでください。'])
                ->withInput();
        }

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
        // ★ 3日前ルールの適用
        $minDate = Carbon::today()->addDays(3)->toDateString();

        $validated = $request->validate([
            'receive_method' => ['required','in:store,delivery'], // 店頭=store 配送=delivery
            'receive_date'   => ['required','date','after_or_equal:' . $minDate],
            'receive_time'   => ['required'], // "10:00" 等
        ], [
            'receive_date.after_or_equal' => '受取日は ' . Carbon::parse($minDate)->isoFormat('M月D日(ddd)') . ' 以降を選んでください。',
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
