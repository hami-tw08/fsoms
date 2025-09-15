<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CartController extends Controller
{
    /** 配送時の最低金額（税込） */
    private const MIN_DELIVERY_TOTAL = 4000;

    /**
     * 予約商品一覧（カート）表示
     */
    public function index(Request $request): View
    {
        $cart = (array) session('reservation.cart', []);
        $meta = (array) session('reservation.meta', []); // ['method' => store|delivery, 'date' => Y-m-d, 'time' => H:i]

        $total = collect($cart)->sum(fn ($row) => (int) $row['price'] * (int) $row['qty']);
        $isDelivery = ($meta['method'] ?? null) === 'delivery';
        $canProceed = !$isDelivery || $total >= self::MIN_DELIVERY_TOTAL;

        return view('cart.index', compact('cart', 'total', 'meta', 'isDelivery', 'canProceed'));
    }

    /**
     * 商品をカートへ追加
     */
    public function add(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'name'       => ['required', 'string', 'max:255'],
            'price'      => ['required', 'integer', 'min:0'], // 税込・円
            'qty'        => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cart = (array) session('reservation.cart', []);

        // 同一 product_id があれば数量だけ加算
        $existingKey = collect($cart)->search(
            fn ($row) => (int) ($row['product_id'] ?? 0) === (int) $validated['product_id']
        );

        if ($existingKey !== false) {
            $cart[$existingKey]['qty'] = min(99, (int) $cart[$existingKey]['qty'] + (int) $validated['qty']);
        } else {
            $cart[] = [
                'row_id'     => (string) Str::uuid(),
                'product_id' => (int) $validated['product_id'],
                'name'       => $validated['name'],
                'price'      => (int) $validated['price'],
                'qty'        => (int) $validated['qty'],
            ];
        }

        session()->put('reservation.cart', array_values($cart));

        return redirect()->route('cart.index');
    }

    /**
     * 行の数量を更新
     */
    public function update(Request $request, string $rowId): RedirectResponse
    {
        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $updated = collect(session('reservation.cart', []))
            ->map(function ($row) use ($rowId, $validated) {
                if (($row['row_id'] ?? null) === $rowId) {
                    $row['qty'] = (int) $validated['qty'];
                }
                return $row;
            })
            ->values()
            ->all();

        session()->put('reservation.cart', $updated);

        return back();
    }

    /**
     * 行を削除
     */
    public function remove(Request $request, string $rowId): RedirectResponse
    {
        $cart = collect(session('reservation.cart', []))
            ->reject(fn ($row) => ($row['row_id'] ?? null) === $rowId)
            ->values()
            ->all();

        session()->put('reservation.cart', $cart);

        return back();
    }

    /**
     * カートを全クリア
     */
    public function clear(): RedirectResponse
    {
        session()->forget('reservation.cart');

        return back();
    }
}
