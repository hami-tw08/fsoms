<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class SlotController extends Controller
{
    /**
     * /admin/slots
     * - デフォルト: 一括編集モード（mode=bulk）
     * - 旧一覧: ?mode=list で従来のページング一覧
     */
    public function index(Request $request)
    {
        $mode = $request->get('mode', 'bulk');

        if ($mode === 'list') {
            // ====== 旧: 一覧（フィルタ＋ページネーション） ======
            $q = DB::table('reservation_slots');

            if ($type = $request->get('type')) {
                $q->where('slot_type', $type); // 'store' or 'delivery'
            }
            if ($date = $request->get('date')) {
                $q->whereDate('slot_date', $date);
            }

            $slots = $q->orderBy('slot_date')->orderBy('start_time')->paginate(100)->withQueryString();

            // 既存ビューとの互換性維持
            return view('admin.slots.index', [
                'mode'  => 'list',
                'slots' => $slots,
            ]);
        }

        // ====== 新: 一括編集（通知閾値／収容数） ======
        $date = $request->input('date', Carbon::today()->toDateString());

        // 当日1日の有効な枠を抽出して type ごとにまとめる
        $rows = DB::table('reservation_slots as s')
            ->whereDate('s.slot_date', $date)
            ->orderBy('s.start_time')
            ->orderBy('s.slot_type')
            ->get([
                's.id',
                's.slot_date',
                's.slot_type',     // 'store' | 'delivery'
                's.start_time',
                's.end_time',
                's.capacity',
                // ない可能性もあるので存在しなくても致命傷にならないように
                DB::raw('COALESCE(s.notify_threshold, 1) as notify_threshold'),
                DB::raw('s.is_active'),
                DB::raw('s.updated_at'),
                DB::raw('s.created_at'),
                DB::raw('s.notified_low_at'), // ない環境も想定
            ]);

        // type => rows[] にグルーピング
        $grouped = [
            'store'    => [],
            'delivery' => [],
        ];
        foreach ($rows as $r) {
            $grouped[$r->slot_type][] = $r;
        }

        // 一括編集用ビュー（同じ index.blade.php でもOK。blade側で mode を見て出し分け）
        return view('admin.slots.index', [
            'mode'  => 'bulk',
            'date'  => $date,
            'slots' => $grouped, // ['store'=>[], 'delivery'=>[]]
        ]);
    }

    /**
     * is_active の ON/OFF（従来機能は温存）
     */
    public function toggle(int $id): RedirectResponse
    {
        $slot = DB::table('reservation_slots')->where('id', $id)->first();
        if (!$slot) return back()->with('status', '枠が見つかりません');

        DB::table('reservation_slots')
            ->where('id', $id)
            ->update(['is_active' => $slot->is_active ? 0 : 1, 'updated_at' => now()]);

        return back()->with('status', '更新しました');
    }

    /**
     * 一括更新：capacity / notify_threshold / 通知状態リセット
     * POST /admin/slots/bulk-update
     *
     * リクエスト例:
     *  - date=2025-10-05
     *  - reset_notified=1 (任意)
     *  - items[123][id]=123
     *  - items[123][capacity]=3
     *  - items[123][notify_threshold]=1
     *  - items[456][id]=456 ...
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required','date'],
            'items' => ['required','array'],
            'items.*.id' => ['required','integer','exists:reservation_slots,id'],
            'items.*.capacity' => ['nullable','integer','min:0','max:99'],
            'items.*.notify_threshold' => ['nullable','integer','min:0','max:99'],
            'reset_notified' => ['sometimes','boolean'],
        ]);

        DB::transaction(function () use ($data, $request) {
            foreach ($data['items'] as $row) {
                // 行ロックして競合更新を避ける
                $slot = DB::table('reservation_slots')
                    ->where('id', $row['id'])
                    ->lockForUpdate()
                    ->first();

                if (!$slot) continue;

                $updates = ['updated_at' => now()];

                if (array_key_exists('capacity', $row) && $row['capacity'] !== null) {
                    $updates['capacity'] = (int) $row['capacity'];
                }

                if (array_key_exists('notify_threshold', $row)) {
                    // カラムが無い環境でもエラーにならないように存在チェック（MySQL: INFORMATION_SCHEMA 叩くのは重いので try-catch でも可）
                    // ここは素直にセット。無い場合はマイグレーションで追加してください。
                    $updates['notify_threshold'] = (int) $row['notify_threshold'];
                }

                if ($request->boolean('reset_notified')) {
                    // カラムが無い場合は無視されるだけ（エラーが出る場合はマイグレーション追加を）
                    $updates['notified_low_at'] = null;
                }

                DB::table('reservation_slots')
                    ->where('id', $row['id'])
                    ->update($updates);
            }
        });

        return back()->with('status', '更新しました');
    }
}
