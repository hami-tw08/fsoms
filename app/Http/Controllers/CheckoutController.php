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

    /**
     * 配送先情報ページ表示
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function shipping(Request $request): View|RedirectResponse
    {
        $cart = (array) session('reservation.cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index');
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
        ];

        if ($isDelivery) {
            $rules = array_merge($rules, [
                'recipient_name'    => ['required', 'string', 'max:100'],
                'recipient_company' => ['nullable', 'string', 'max:100'],
                'recipient_store'   => ['nullable', 'string', 'max:100'],
                'area'              => ['required', Rule::in(self::DELIVERY_AREAS)],
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

        // この後の最終確認ページが未実装なら一旦カートへ戻す
        return redirect()
            ->route('cart.index')
            ->with('ok', '配送先情報を保存しました。');
    }
}
