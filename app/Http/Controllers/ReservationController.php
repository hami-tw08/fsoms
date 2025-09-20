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
    /**
     * 予約トップ：月カレンダー＋残数表示
     */
    public function create(Request $request)
    {
        // 3日前ルール（例：9/15なら 9/18 以降）
        $minDate = Carbon::today()->addDays(3);

        // 表示月（?month=YYYY-MM）
        $month = $request->query('month');
        $firstDay = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : Carbon::today()->startOfMonth();

        // カレンダー表示範囲（週頭:月曜〜週末:日曜）
        $start = $firstDay->copy()->startOfWeek(Carbon::MONDAY);
        $end   = $firstDay->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // === 月内の空き枠残数（店頭/配達）を集計 ===
        $monthStart = $firstDay->copy()->startOfMonth()->toDateString();
        $monthEnd   = $firstDay->copy()->endOfMonth()->toDateString();
        $shopId = 1; // 1店舗運用

        $rows = DB::table('reservation_slots as s')
            ->leftJoin('reservations as r', function ($j) {
                $j->on('r.slot_id', '=', 's.id')
                  ->whereIn('r.status', ['booked', 'completed']);
            })
            ->where('s.shop_id', $shopId)
            ->whereBetween('s.slot_date', [$monthStart, $monthEnd])
            ->whereDate('s.slot_date', '>=', $minDate->toDateString()) // ★ 3日前未満を除外
            ->where('s.is_active', true)
            ->groupBy('s.slot_date', 's.slot_type')
            ->get([
                's.slot_date',
                's.slot_type',
                DB::raw('SUM(s.capacity) as total_capacity'),
                DB::raw('COUNT(r.id) as total_booked'),
            ]);

        // stats['YYYY-MM-DD']['store'|'delivery'] = 残数
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
                $beforeMin = $cursor->lt($minDate); // 3日前未満

                $week[] = [
                    'date'         => $dateStr,
                    'day'          => $cursor->day,
                    'in_month'     => $cursor->month === $firstDay->month,
                    'is_today'     => $cursor->isToday(),
                    'before_min'   => $beforeMin,
                    // 集計は3日前未満を除外済み → 自然と 0
                    'remain_store' => $stats[$dateStr]['store']    ?? 0,
                    'remain_deliv' => $stats[$dateStr]['delivery'] ?? 0,
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        $prevMonth = $firstDay->copy()->subMonth()->format('Y-m');
        $nextMonth = $firstDay->copy()->addMonth()->format('Y-m');

        // ★ view パスは resources/views/reserve/create.blade.php を想定
        return view('reserve.create', [
            'weeks'     => $weeks,
            'firstDay'  => $firstDay,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'minDate'   => $minDate,
        ]);
    }

    /**
     * 本予約の登録（最終POST）
     */
    public function store(StoreReservationRequest $request)
    {
        $slot = ReservationSlot::findOrFail($request->slot_id);

        // サーバ側 3日前ガード
        $minDate = Carbon::today()->addDays(3)->toDateString();
        if (Carbon::parse($slot->slot_date)->lt($minDate)) {
            return back()
                ->withErrors([
                    'slot_id' => '予約日は ' . Carbon::parse($minDate)->isoFormat('M月D日(ddd)') . ' 以降を選んでください。',
                ])
                ->withInput();
        }

        $product = Product::findOrFail($request->product_id);
        $total = $product->price * (int)$request->quantity;

        Reservation::create([
            'slot_id'      => $slot->id,
            'user_id'      => optional($request->user())->id, // ゲストなら null
            'product_id'   => $product->id,
            'quantity'     => (int)$request->quantity,
            'total_amount' => $total,
            'status'       => 'booked',
            'notes'        => $request->notes,
            // 配達向け
            'delivery_area'        => $request->delivery_area,
            'delivery_postal_code' => $request->delivery_postal_code,
            'delivery_address'     => $request->delivery_address,
            // ゲスト予約
            'guest_name'  => $request->guest_name,
            'guest_phone' => $request->guest_phone,
        ]);

        return redirect()
            ->route('reserve.create', ['month' => $request->query('month')])
            ->with('status', '予約を作成しました！');
    }

    /**
     * カレンダー右側の“中間保存”POST（受取り方法・日付・時間）→ セッション保存
     * Blade: <form id="reserveMetaForm" action="{{ route('reserve.storeCreateStep') }}">
     */
    public function storeCreateStep(Request $request)
    {
        // 3日前ルール
        $minDate = Carbon::today()->addDays(3)->toDateString();

        $validated = $request->validate([
            'receive_method'     => ['required', 'in:store,delivery'], // 店頭=store 配送=delivery
            'receive_date'       => ['required', 'date', 'after_or_equal:' . $minDate],
            'receive_time'       => ['required', 'date_format:H:i'],   // "10:00"
            'receive_time_start' => ['nullable', 'date_format:H:i'],
            'receive_time_end'   => ['nullable', 'date_format:H:i'],
        ], [
            'receive_date.after_or_equal' =>
                '受取日は ' . Carbon::parse($minDate)->isoFormat('M月D日(ddd)') . ' 以降を選んでください。',
        ]);

        // セッションへ保存（次画面の商品選択で利用想定）
        session()->put('reservation.meta', [
            'method'     => $validated['receive_method'],
            'date'       => $validated['receive_date'],
            'time'       => $validated['receive_time'],
            'time_start' => $validated['receive_time_start'] ?? $validated['receive_time'],
            'time_end'   => $validated['receive_time_end']   ?? null,
        ]);

        // まずは予約トップに戻して案内（products.index 未定義でも落ちない）
        return redirect()
            ->route('reserve.create', $request->only('month'))
            ->with('status', '受取り情報を保存しました。商品選択へ進めます。');
        // もし商品選択ルートがあるなら：
        // return redirect()->route('products.index');
    }

    /**
     * 空き枠JSON（AJAX）
     * GET /slots?date=YYYY-MM-DD&slot_type=store|delivery
     * レスポンス: [{id,start_time,end_time,remaining}, ...]
     */
    public function slots(Request $request)
    {
        $request->validate([
            'date'      => ['required', 'date'],
            'slot_type' => ['required', 'in:store,delivery'],
        ]);

        $date = $request->query('date');
        $type = $request->query('slot_type');
        if (!$date || !in_array($type, ['store','delivery'], true)) {
            return response()->json([], 400);
        }

        $shopId = 1; // 1店舗運用前提

        // 3日前未満は返さない（フロントの制御と合わせる）
        $minDate = Carbon::today()->addDays(3)->toDateString();
        if (Carbon::parse($date)->lt($minDate)) {
            return response()->json([]);
        }

        // 定義されている実枠（is_active=1）と予約を突き合わせて残数を返す
        $rows = DB::table('reservation_slots as s')
            ->leftJoin('reservations as r', function ($j) {
                $j->on('r.slot_id', '=', 's.id')
                  ->whereIn('r.status', ['booked','completed']);
            })
            ->where('s.shop_id', $shopId)
            ->where('s.slot_date', $date)
            ->where('s.slot_type', $type)
            ->where('s.is_active', true)
            ->groupBy('s.id','s.start_time','s.end_time','s.capacity')
            ->orderBy('s.start_time')
            ->get([
                's.id',
                's.start_time',
                's.end_time',
                DB::raw('GREATEST(s.capacity - COUNT(r.id), 0) as remaining'),
            ]);

        return response()->json($rows);
    }
}
