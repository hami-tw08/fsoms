<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use App\Models\ReservationSlot; // ★ 追加（bulkでEloquent使用）

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

            return view('admin.slots.index', [
                'mode'  => 'list',
                'slots' => $slots,
            ]);
        }

        // ====== 新: 一括編集（通知閾値／収容数） ======
        $date = $request->input('date', Carbon::today()->toDateString());

        // Eloquent + withCount で booked/completed 件数を集計 → remaining を計算
        $slots = ReservationSlot::query()
            ->whereDate('slot_date', $date)
            ->orderBy('start_time')
            ->orderBy('slot_type')
            ->withCount(['reservations as booked_count' => function ($q) {
                $q->whereIn('status', ['booked', 'completed']);
            }])
            ->get();

        // 動的プロパティとして remaining を付与、notify_threshold のデフォルトも設定
        $slots->each(function ($s) {
            $booked = (int) ($s->booked_count ?? 0);
            $s->remaining = max(0, ((int) $s->capacity) - $booked);
            if ($s->notify_threshold === null) {
                $s->notify_threshold = 1; // カラム未設定やNULLでも安全
            }
        });

        // Blade 側の想定に合わせて type ごとにグループ化
        $grouped = [
            'store'    => $slots->where('slot_type', 'store')->values(),
            'delivery' => $slots->where('slot_type', 'delivery')->values(),
        ];

        return view('admin.slots.index', [
            'mode'  => 'bulk',
            'date'  => $date,
            'slots' => $grouped,
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
                    $updates['notify_threshold'] = (int) $row['notify_threshold'];
                }

                if ($request->boolean('reset_notified')) {
                    $updates['notified_low_at'] = null;
                }

                DB::table('reservation_slots')->where('id', $row['id'])->update($updates);
            }
        });

        return back()->with('status', '更新しました');
    }
}
