<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB; // ★ 追加：DB保存で使用

class CheckoutController extends Controller
{
    /** 配送時の最低金額（税込） */
    private const MIN_DELIVERY_TOTAL = 4000;

    /** 許可する配送エリア */
    private const DELIVERY_AREAS = ['浪江', '双葉', '大熊', '小高区'];

    /** 配送料（エリア別） */
    private const DELIVERY_FEES = [
        '浪江' => 0,
        '双葉' => 900,
        '大熊' => 900,
        '小高区' => 900,
    ];

    /**
     * 配送先情報ページ表示
     */
    public function shipping(Request $request): View|RedirectResponse
    {
        $cart = (array) session('reservation.cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('cart_error', 'カートが空です。');
        }

        $meta = (array) session('reservation.meta', []);
        $isDelivery = ($meta['method'] ?? null) === 'delivery';

        $total = collect($cart)->sum(fn ($r) => (int) $r['price'] * (int) $r['qty']);

        // 配送かつ4,000円未満はカートに戻す
        if ($isDelivery && $total < self::MIN_DELIVERY_TOTAL) {
            return redirect()
                ->route('cart.index')
                ->with('cart_error', '配送は税込4,000円以上でお願いします。');
        }

        return view('checkout.shipping', compact('meta', 'isDelivery', 'cart', 'total'));
    }

    /**
     * 配送先情報の保存（セッション）
     */
    public function storeShipping(Request $request): RedirectResponse
    {
        $meta = (array) session('reservation.meta', []);
        $isDelivery = ($meta['method'] ?? null) === 'delivery';

        $rules = [
            'orderer_name'  => ['required', 'string', 'max:100'],
            'orderer_phone' => ['required', 'string', 'max:50'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ];

        if ($isDelivery) {
            $rules = array_merge($rules, [
                'recipient_name'    => ['required', 'string', 'max:100'],
                'recipient_company' => ['nullable', 'string', 'max:100'],
                'recipient_store'   => ['nullable', 'string', 'max:100'],
                'area'              => ['required', Rule::in(self::DELIVERY_AREAS)],
                'postal_code'       => ['nullable', 'string', 'max:20'],
                'address'           => ['required', 'string', 'max:255'],
            ]);
        } else {
            // 店頭受取：受取者情報は任意
            $rules = array_merge($rules, [
                'recipient_name'    => ['nullable', 'string', 'max:100'],
                'recipient_company' => ['nullable', 'string', 'max:100'],
                'recipient_store'   => ['nullable', 'string', 'max:100'],
            ]);
        }

        $data = $request->validate($rules);

        session()->put('reservation.shipping', $data);

        // 最終確認へ
        return redirect()
            ->route('checkout.confirm')
            ->with('ok', '配送先情報を保存しました。');
    }

    /**
     * 最終確認画面
     */
    public function confirm(Request $request): View|RedirectResponse
    {
        $cart     = (array) session('reservation.cart', []);
        $meta     = (array) session('reservation.meta', []);
        $shipping = (array) session('reservation.shipping', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('cart_error', 'カートが空です。');
        }

        $method = $meta['method'] ?? null; // 'store' or 'delivery'
        if (!in_array($method, ['store', 'delivery'], true)) {
            return redirect()->route('reserve.create')->with('cart_error', '受取方法を選択してください。');
        }

        $isDelivery = $method === 'delivery';

        // 合計金額
        $total = 0;
        foreach ($cart as $row) {
            $total += (int) ($row['price'] ?? 0) * (int) ($row['qty'] ?? 0);
        }

        // 配送チェック
        $deliveryFee = 0;
        $deliveryArea = $shipping['area'] ?? null;

        if ($isDelivery) {
            if ($total < self::MIN_DELIVERY_TOTAL) {
                return redirect()->route('cart.index')->with('cart_error', '配送は税込4,000円以上でお願いします。');
            }
            if (!$deliveryArea || !in_array($deliveryArea, self::DELIVERY_AREAS, true)) {
                return redirect()->route('checkout.shipping')->with('cart_error', '配送エリアが不正です。');
            }
            $deliveryFee = self::DELIVERY_FEES[$deliveryArea] ?? 900;
        }

        $grandTotal = $total + $deliveryFee;

        // confirm.blade.php に合わせた表示用整形
        $shippingForView = [
            'guest_name'           => $shipping['orderer_name']  ?? '',
            'guest_phone'          => $shipping['orderer_phone'] ?? '',
            'delivery_postal_code' => $shipping['postal_code']   ?? '',
            'delivery_address'     => $shipping['address']       ?? '',
            'notes'                => $shipping['notes']         ?? '',
        ];

        $date = $meta['date'] ?? null;
        $time = $meta['time'] ?? null;

        return view('checkout.confirm', [
            'cart'         => $cart,
            'meta'         => $meta,
            'shipping'     => $shippingForView,
            'isDelivery'   => $isDelivery,
            'total'        => $total,
            'deliveryFee'  => $deliveryFee,
            'grandTotal'   => $grandTotal,
            'date'         => $date,
            'time'         => $time,
            'deliveryArea' => $deliveryArea,
        ]);
    }

    /**
     * 注文確定（DB保存 → 完了画面へ）
     * - 既存の完了画面表示は維持（セッション reservation.completed）
     * - テーブル設計（reservations: slot_id/customer_id/product_id/...）に合わせて保存
     * - 複数商品は「複数行のreservation」で対応
     */
    public function place(Request $request): RedirectResponse
    {
        $cart     = (array) session('reservation.cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('cart_error', 'カートが空です。');
        }

        $meta     = (array) session('reservation.meta', []);
        $shipping = (array) session('reservation.shipping', []);

        // 必須メタ
        $method    = $meta['method']     ?? null; // 'store'|'delivery'
        $date      = $meta['date']       ?? null; // Y-m-d
        $timeStart = $meta['time_start'] ?? ($meta['time'] ?? null); // H:i
        $timeEnd   = $meta['time_end']   ?? null; // H:i or null

        if (!in_array($method, ['store','delivery'], true) || !$date || !$timeStart) {
            return redirect()->route('reserve.create')->with('cart_error', '受取方法・日時の情報が不足しています。');
        }

        // 合計計算（完了画面用）
        $total = 0;
        foreach ($cart as $row) {
            $total += (int) ($row['price'] ?? 0) * (int) ($row['qty'] ?? 0);
        }

        // 配送関連
        $isDelivery = ($method === 'delivery');
        $deliveryAreaJp = $shipping['area'] ?? null;
        $deliveryFee = $isDelivery ? (self::DELIVERY_FEES[$deliveryAreaJp] ?? 900) : 0;
        $grandTotal  = $total + $deliveryFee;

        // 日本語→enum（reservations.delivery_area）
        $areaMap = ['浪江'=>'namie','双葉'=>'futaba','大熊'=>'okuma','小高区'=>'odaka'];
        $deliveryAreaEnum = $isDelivery ? ($areaMap[$deliveryAreaJp] ?? null) : null;

        // ★★★ 追加：ここで user_id を取得（ゲストは null）
        $userId = optional($request->user())->id;

        // ===== DB保存（トランザクション） =====
        try {
            DB::transaction(function () use ($cart, $method, $date, $timeStart, $timeEnd, $isDelivery, $deliveryAreaEnum, $shipping, $userId) {
                // 1) スロット特定（1店舗運用）
                $slotQ = DB::table('reservation_slots')
                    ->where('shop_id', 1)
                    ->whereDate('slot_date', $date)
                    ->where('slot_type', $method)
                    ->where('start_time', $timeStart . ':00');

                if (!empty($timeEnd)) {
                    $slotQ->where('end_time', $timeEnd . ':00');
                }

                $slot = $slotQ->lockForUpdate()->first();
                if (!$slot) {
                    throw new \RuntimeException('該当する予約枠が見つかりませんでした。別の時間をお選びください。');
                }

                // 2) 空き確認（booked/completed）
                $booked = DB::table('reservations')
                    ->where('slot_id', $slot->id)
                    ->whereIn('status', ['booked','completed'])
                    ->lockForUpdate()
                    ->count();

                if ($booked >= (int) $slot->capacity) {
                    throw new \RuntimeException('申し訳ありません。この枠は満席になりました。別の時間をお選びください。');
                }

                // 3) 顧客レコード（customers）準備：電話で同定
                $ordererName  = $shipping['orderer_name']  ?? '';
                $ordererPhone = $shipping['orderer_phone'] ?? '';
                if (!$ordererName || !$ordererPhone) {
                    throw new \RuntimeException('注文者の氏名・電話が不足しています。');
                }

                $customer = DB::table('customers')->where('phone', $ordererPhone)->first();
                if (!$customer) {
                    $customerId = DB::table('customers')->insertGetId([
                        'name'       => $ordererName,
                        'phone'      => $ordererPhone,
                        'email'      => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $customerId = $customer->id;
                }

                // 4) カートの各商品を reservations へ登録（複数商品→複数レコード）
                foreach ($cart as $row) {
                    $productId = (int)($row['product_id'] ?? 0);
                    $qty       = (int)($row['qty'] ?? 0);
                    $price     = (int)($row['price'] ?? 0);
                    if ($productId <= 0 || $qty <= 0) {
                        continue;
                    }

                    // 外部キーエラー回避のため products の存在確認
                    $productExists = DB::table('products')->where('id', $productId)->exists();
                    if (!$productExists) {
                        continue; // 見つからなければこの行は保存スキップ
                    }

                    DB::table('reservations')->insert([
                        'slot_id'      => $slot->id,
                        'user_id'      => $userId,

                        // 顧客IDは別でひもづけ済み（customers）
                        'customer_id'  => $customerId,

                        'product_id'   => $productId,
                        'quantity'     => $qty,
                        'total_amount' => $price * $qty,
                        'status'       => 'booked',
                        'notes'        => $shipping['notes'] ?? null,

                        // ▼▼ ここが重要：注文者（=旧 guest_* として保存しておく）
                        'guest_name'   => $shipping['orderer_name']  ?? null,
                        'guest_phone'  => $shipping['orderer_phone'] ?? null,

                        // ▼ 配達関連（DBの既存カラム）
                        'delivery_area'        => $isDelivery ? $deliveryAreaEnum : null,
                        'delivery_postal_code' => $isDelivery ? ($shipping['postal_code'] ?? null) : null,
                        'delivery_address'     => $isDelivery ? ($shipping['address'] ?? null)      : null,

                        // ▼ 追加：お客さま入力を丸ごと保存（店頭でも配達でも）
                        'shipping_json' => json_encode([
                            'orderer_name'     => $shipping['orderer_name']  ?? null,
                            'orderer_phone'    => $shipping['orderer_phone'] ?? null,
                            'recipient_name'   => $shipping['recipient_name']   ?? null,
                            'recipient_company'=> $shipping['recipient_company'] ?? null,
                            'recipient_store'  => $shipping['recipient_store']   ?? null,
                            'area_jp'          => $shipping['area'] ?? null, // JP表示用に保持
                            'postal_code'      => $shipping['postal_code'] ?? null,
                            'address'          => $shipping['address'] ?? null,
                            'notes'            => $shipping['notes'] ?? null,
                        ], JSON_UNESCAPED_UNICODE),

                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                }
            });

        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('checkout.confirm')
                ->with('cart_error', $e->getMessage() ?: '予約の保存に失敗しました。もう一度お試しください。');
        }

        // ===== 完了画面用セッション（従来どおり維持） =====
        session()->put('reservation.completed', [
            'cart'         => $cart,
            'meta'         => $meta,
            'deliveryArea' => $shipping['area'] ?? null,
            'total'        => $total,
            'deliveryFee'  => $deliveryFee,
            'grandTotal'   => $grandTotal,
        ]);

        // 元データは掃除（完了表示用は残す）
        session()->forget('reservation.cart');
        session()->forget('reservation.meta');
        session()->forget('reservation.shipping');

        return redirect()->route('checkout.complete')->with('ok', 'ご予約を受け付けました。');
    }

    /**
     * 完了画面
     * - reservation.completed の内容をビューに渡す
     */
    public function complete(): View
    {
        return view('checkout.complete', [
            'cart'         => session('reservation.completed.cart'),
            'meta'         => session('reservation.completed.meta'),
            'deliveryArea' => session('reservation.completed.deliveryArea'),
            'total'        => session('reservation.completed.total'),
            'deliveryFee'  => session('reservation.completed.deliveryFee'),
            'grandTotal'   => session('reservation.completed.grandTotal'),
        ]);
    }
}
