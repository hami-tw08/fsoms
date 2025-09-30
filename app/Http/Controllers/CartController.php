<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /** セッションキー */
    private const CART_KEY = 'reservation.cart';
    private const META_KEY = 'reservation.meta';

    /** 配送時の最低合計（税込円） */
    private const MIN_DELIVERY_TOTAL = 4000;

    /** 数量の下限/上限 */
    private const QTY_MIN = 1;
    private const QTY_MAX = 99;

    /**
     * 予約商品一覧（カート）表示
     * - 行ごとの meta(method/date/time) でグルーピングして返す
     */
    public function index(Request $request): View
    {
        $cart = $this->getCart($request);

        // グルーピング（meta_key = method|date|time）
        $groups = collect($cart)
            ->groupBy(fn ($r) => (string)($r['meta_key'] ?? '|||')) // 空でもキー化
            ->map(function ($rows, $metaKey) {
                // メタの復元（空なら null）
                [$method, $date, $time] = $this->splitMetaKey($metaKey);

                $items = array_values($rows->all());
                $total = $this->calcTotal($items);
                $isDelivery = ($method === 'delivery');
                $canProceed = $this->canProceed($isDelivery, $total);

                return [
                    'meta_key'   => $metaKey,
                    'method'     => $method, // 'store' | 'delivery' | null
                    'date'       => $date,   // 'Y-m-d' | null
                    'time'       => $time,   // 'H:i'   | null
                    'items'      => $items,
                    'total'      => $total,
                    'isDelivery' => $isDelivery,
                    'canProceed' => $canProceed,
                ];
            })
            // 表示順：日付昇順 → 時間昇順 → method(store先) → 合計降順
            ->sortBy([
                fn ($g) => $g['date'] ?? '9999-12-31',
                fn ($g) => $g['time'] ?? '99:99',
                fn ($g) => $g['method'] === 'store' ? 0 : 1,
                fn ($g) => -$g['total'],
            ])
            ->values()
            ->all();

        // 全体メタ（今のセッション状態を参考までに渡す）
        $meta = $this->getMeta($request);

        return view('cart.index', [
            'groups' => $groups,
            'meta'   => $meta,
        ]);
    }

    /**
     * 商品をカートへ追加（products/show から）
     * - 追加時点の受取メタ(method/date/time)を行にスナップショット保存
     * - 同一 product_id かつ 同一 meta_key は数量を集約
     */
    public function add(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'product_id' => ['required', 'integer'],
            'name'       => ['required', 'string', 'max:255'],
            'price'      => ['required', 'integer', 'min:0'], // 税込・円
            'qty'        => ['nullable', 'integer', 'min:' . self::QTY_MIN, 'max:' . self::QTY_MAX],
        ]);

        // 受取メタが未設定ならエラー（まず予約作成ステップで日付/方法/時間を確定させる想定）
        $meta = $this->getMeta($request);
        if (!$this->isValidMeta($meta)) {
            return back()->with('cart_error', '受取方法・日時を先に選択してください。');
        }

        $metaKey = $this->makeMetaKey($meta); // method|date|time
        $qty     = $this->clampQty((int)($v['qty'] ?? 1));
        $cart    = $this->getCart($request);

        // 同一 product_id & 同一 meta_key なら数量加算
        $idx = $this->findIndexByProductIdAndMetaKey($cart, (int)$v['product_id'], $metaKey);

        if ($idx !== null) {
            $cart[$idx]['qty'] = $this->clampQty(((int)$cart[$idx]['qty']) + $qty);
        } else {
            $cart[] = [
                'row_id'     => (string) Str::uuid(),
                'product_id' => (int) $v['product_id'],
                'name'       => $v['name'],
                'price'      => (int) $v['price'],
                'qty'        => $qty,
                // スナップショット（表示/集計は meta_key から復元する）
                'meta'       => [
                    'method' => $meta['method'] ?? null,
                    'date'   => $meta['date']   ?? null,
                    'time'   => $meta['time']   ?? null,
                ],
                'meta_key'   => $metaKey,
            ];
        }

        $this->putCart($request, $cart);

        return redirect()
            ->route('cart.index')
            ->with('ok', '商品を追加しました。');
    }

    /**
     * 行の数量を更新
     */
    public function update(Request $request, string $rowId): RedirectResponse
    {
        $v = $request->validate([
            'qty' => ['required', 'integer', 'min:' . self::QTY_MIN, 'max:' . self::QTY_MAX],
        ]);

        $qty  = $this->clampQty((int)$v['qty']);
        $cart = $this->getCart($request);

        $found = false;
        foreach ($cart as &$row) {
            if (($row['row_id'] ?? null) === $rowId) {
                $row['qty'] = $qty;
                $found = true;
                break;
            }
        }
        unset($row);

        if (!$found) {
            return back()->with('cart_error', '対象の行が見つかりませんでした。');
        }

        $this->putCart($request, $cart);
        return back()->with('ok', '数量を更新しました。');
    }

    /**
     * 行を削除
     */
    public function remove(Request $request, string $rowId): RedirectResponse
    {
        $cart   = $this->getCart($request);
        $before = count($cart);

        $cart = array_values(array_filter($cart, fn ($r) => ($r['row_id'] ?? null) !== $rowId));

        if (count($cart) === $before) {
            return back()->with('cart_error', '対象の行が見つかりませんでした。');
        }

        $this->putCart($request, $cart);
        return back()->with('ok', '削除しました。');
    }

    /**
     * カートを全クリア
     */
    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget(self::CART_KEY);
        return back()->with('ok', 'すべて削除しました。');
    }

    // -----------------------
    // Helpers
    // -----------------------

    /** セッションからカート配列を取得（最低限のスキーマ整形） */
    private function getCart(Request $request): array
    {
        $cart = (array)$request->session()->get(self::CART_KEY, []);
        return array_values(array_map(function ($r) {
            // row_id が無ければ採番、qty/price のクランプ
            $method = $r['meta']['method'] ?? null;
            $date   = $r['meta']['date']   ?? null;
            $time   = $r['meta']['time']   ?? null;
            $metaKey = $r['meta_key'] ?? $this->makeMetaKey(['method'=>$method,'date'=>$date,'time'=>$time]);

            return [
                'row_id'     => (string)($r['row_id'] ?? (string)Str::uuid()),
                'product_id' => (int)($r['product_id'] ?? 0),
                'name'       => (string)($r['name'] ?? ''),
                'price'      => max(0, (int)($r['price'] ?? 0)),
                'qty'        => $this->clampQty((int)($r['qty'] ?? 1)),
                'meta'       => [
                    'method' => $method,
                    'date'   => $date,
                    'time'   => $time,
                ],
                'meta_key'   => (string)$metaKey,
            ];
        }, $cart));
    }

    /** セッションへカートを保存 */
    private function putCart(Request $request, array $cart): void
    {
        $request->session()->put(self::CART_KEY, array_values($cart));
    }

    /** 現在の受取メタ（セッションの予約情報） */
    private function getMeta(Request $request): array
    {
        return (array)$request->session()->get(self::META_KEY, []); // ['method'=>'store|delivery','date'=>'Y-m-d','time'=>'H:i']
    }

    /** メタが妥当か（method/date/time が全部ある） */
    private function isValidMeta(array $meta): bool
    {
        return !empty($meta['method']) && !empty($meta['date']) && !empty($meta['time']);
    }

    /** 合計金額（円） */
    private function calcTotal(array $rows): int
    {
        $sum = 0;
        foreach ($rows as $r) {
            $sum += ((int)$r['price']) * ((int)$r['qty']);
        }
        return $sum;
    }

    /** 受取方法＝配送？ */
    private function isDeliveryFromKey(string $metaKey): bool
    {
        [$method] = $this->splitMetaKey($metaKey);
        return $method === 'delivery';
    }

    /** 次へ進めるか判定 */
    private function canProceed(bool $isDelivery, int $total): bool
    {
        return !$isDelivery || $total >= self::MIN_DELIVERY_TOTAL;
    }

    /** 数量のクランプ */
    private function clampQty(int $qty): int
    {
        return max(self::QTY_MIN, min(self::QTY_MAX, $qty));
    }

    /** product_id & meta_key で既存行のインデックス検索（見つからなければ null） */
    private function findIndexByProductIdAndMetaKey(array $cart, int $productId, string $metaKey): ?int
    {
        foreach ($cart as $i => $r) {
            if ((int)($r['product_id'] ?? 0) === $productId && (string)($r['meta_key'] ?? '') === $metaKey) {
                return $i;
            }
        }
        return null;
    }

    /** 'method|date|time' 形式のメタキーを作る */
    private function makeMetaKey(array $meta): string
    {
        $m = (string)($meta['method'] ?? '');
        $d = (string)($meta['date']   ?? '');
        $t = (string)($meta['time']   ?? '');
        return "{$m}|{$d}|{$t}";
    }

    /** メタキーを分割して [method,date,time] を返す */
    private function splitMetaKey(string $metaKey): array
    {
        $parts = explode('|', $metaKey);
        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
            $parts[2] ?? null,
        ];
    }
}
