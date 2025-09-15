<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        // ---- 既存の confirm.blade.php（前回共有分）にフィールドを合わせるための表示用整形 ----
        // 画面側は $shipping['guest_name'], ['guest_phone'], ['delivery_postal_code'], ['delivery_address'], ['notes'] を参照
        $shippingForView = [
            'guest_name'          => $shipping['orderer_name']  ?? '',
            'guest_phone'         => $shipping['orderer_phone'] ?? '',
            'delivery_postal_code'=> $shipping['postal_code']   ?? '',
            'delivery_address'    => $shipping['address']       ?? '',
            'notes'               => $shipping['notes']         ?? '',
        ];
        // -------------------------------------------------------------------

        $date = $meta['date'] ?? null;
        $time = $meta['time'] ?? null;

        // ここで view に渡す $shipping は「表示用整形後」の配列に差し替える
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
     * 注文確定（安全版：DB未保存 → 完了画面へ）
     */
    public function place(Request $request): RedirectResponse
    {
        $request->validate([
            'agree' => ['accepted'],
        ], [
            'agree.accepted' => '利用規約への同意が必要です。',
        ]);

        $cart = (array) session('reservation.cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('cart_error', 'カートが空です。');
        }

        // --- 実装ポイント（後で差し替え） ----------------------------------
        // Reservation::create([...]);
        // ReservationItem::insert([...]);
        // 決済がある場合はここでハンドリング
        // -------------------------------------------------------------------

        // セッション掃除
        session()->forget('reservation.cart');
        session()->forget('reservation.meta');
        session()->forget('reservation.shipping');

        return redirect()->route('checkout.complete')->with('ok', 'ご予約を受け付けました。');
    }

    /**
     * 完了画面
     */
    public function complete(): View
    {
        return view('checkout.complete');
    }
}