<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
//use App\Http\Requests\Admin\ReservationIndexRequest;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB; // ★ 追記：一括削除トランザクション用

class ReservationController extends Controller
{
    //public function index(ReservationIndexRequest $request)
    public function index(Request $request)
    {
        $q      = $request->input('q');
        $method = $request->input('method');
        $area   = $request->input('area');
        $from   = $request->date('from'); // Y-m-d
        $to     = $request->date('to');   // Y-m-d

        $reservations = Reservation::query()
            ->with(['slot','product'])
            ->when($q, function($query, $q) {
                $like = '%'.$q.'%';
                $query->where(function($w) use ($like, $q) {
                    $w->where('guest_name', 'like', $like)
                      ->orWhere('guest_phone', 'like', $like)
                      ->orWhere('notes', 'like', $like)
                      ->orWhere('id', $q)           // 完全一致ID
                      ->orWhere('product_id', $q);  // 完全一致商品ID
                });
            })
            ->when($method, fn($q) => $q->where('method', $method))
            ->when($area,   fn($q) => $q->where('delivery_area', $area))
            ->when($from, function($q) use ($from) {
                $q->whereHas('slot', fn($s) => $s->whereDate('start_at', '>=', $from));
            })
            ->when($to, function($q) use ($to) {
                $q->whereHas('slot', fn($s) => $s->whereDate('start_at', '<=', $to));
            })
            ->latest('id')
            ->paginate(20);

        return view('admin.reservations.index', compact('reservations'));
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['slot','product']);
        return view('admin.reservations.show', compact('reservation'));
    }

    public function export(Request $request): StreamedResponse
    {
        // index と同じ条件で抽出
        $indexRequest = ReservationIndexRequest::createFrom($request);
        $indexRequest->setMethod('GET');
        $query = Reservation::query()
            ->with(['slot','product'])
            ->when($indexRequest->q(), function($query, $q) {
                $like = '%'.$q.'%';
                $query->where(function($w) use ($like, $q) {
                    $w->where('guest_name', 'like', $like)
                      ->orWhere('guest_phone', 'like', $like)
                      ->orWhere('notes', 'like', $like)
                      ->orWhere('id', $q)
                      ->orWhere('product_id', $q);
                });
            })
            ->when($indexRequest->method(), fn($q) => $q->where('method', $indexRequest->method()))
            ->when($indexRequest->area(),   fn($q) => $q->where('delivery_area', $indexRequest->area()))
            ->when($indexRequest->date('from'), function($q) use ($indexRequest) {
                $q->whereHas('slot', fn($s) => $s->whereDate('start_at', '>=', $indexRequest->date('from')));
            })
            ->when($indexRequest->date('to'), function($q) use ($indexRequest) {
                $q->whereHas('slot', fn($s) => $s->whereDate('start_at', '<=', $indexRequest->date('to')));
            })
            ->latest('id');

        $filename = 'reservations_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function() use ($query) {
            $out = fopen('php://output', 'w');
            // ヘッダ
            fputcsv($out, ['id','slot_start','slot_id','guest_name','guest_phone','product_id','product_name','method','delivery_area','created_at']);
            $query->chunk(500, function($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->id,
                        optional($r->slot)->start_at?->format('Y-m-d H:i'),
                        $r->slot_id,
                        $r->guest_name,
                        $r->guest_phone,
                        $r->product_id,
                        optional($r->product)->name,
                        $r->method,
                        $r->delivery_area,
                        optional($r->created_at)?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ===== ここから追記：削除系 =====

    /**
     * 個別削除（1件）
     */
    public function destroy(Reservation $reservation)
    {
        try {
            $reservation->delete();
            return back()->with('success', '予約を削除しました');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', '削除に失敗しました');
        }
    }

    /**
     * 選択削除（複数IDをカンマ区切りで受け取る）
     * リクエスト例: ids="1,2,5"
     */
    public function destroySelected(Request $request)
    {
        $ids = collect(explode(',', (string)$request->input('ids')))
            ->filter(fn($v) => is_numeric($v))
            ->map(fn($v) => (int)$v)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('warning', '削除対象が選択されていません');
        }

        try {
            DB::transaction(function () use ($ids) {
                Reservation::whereIn('id', $ids)->delete();
            });
            return back()->with('success', '選択した予約を削除しました');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', '選択削除に失敗しました');
        }
    }

    /**
     * 全件削除（危険操作）
     */
    public function destroyAll()
    {
        try {
            DB::transaction(function () {
                // 外部キー制約を考慮して truncate は使わない
                Reservation::query()->delete();
            });
            return back()->with('success', '全件削除しました');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', '全件削除に失敗しました');
        }
    }

    // ===== 追記ここまで =====
}
