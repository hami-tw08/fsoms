@extends('layouts.admin')
@section('title','予約一覧')

@section('content')
<div class="flex items-center justify-between mb-4 gap-2">
  {{-- ▼▼▼ 検索・CSV はUIを非表示（コードは残す） ▼▼▼
  <form method="GET" class="w-full">
    <div class="flex flex-wrap items-center gap-2">
      <input class="input input-bordered grow" type="text" name="q"
             value="{{ request('q') }}" placeholder="氏名/電話/備考/ID/商品…">

      <select class="select select-bordered" name="method">
        <option value="">受取/配達(すべて)</option>
        <option value="store" @selected(request('method')==='store')>店頭</option>
        <option value="delivery" @selected(request('method')==='delivery')>配達</option>
      </select>

      <select class="select select-bordered" name="area">
        <option value="">配送エリア(すべて)</option>
        @foreach (['浪江','小高区','双葉','大熊'] as $area)
          <option value="{{ $area }}" @selected(request('area')===$area)>{{ $area }}</option>
        @endforeach
      </select>

      <input type="date" class="input input-bordered" name="from" value="{{ request('from') }}" placeholder="日付From">
      <input type="date" class="input input-bordered" name="to"   value="{{ request('to')   }}" placeholder="日付To">

      <button class="btn btn-primary">検索</button>
      <a class="btn btn-ghost" href="{{ route('admin.reservations.index') }}">クリア</a>

      <a class="btn btn-outline" href="{{ route('admin.reservations.export', request()->query()) }}">
        CSVエクスポート
      </a>
    </div>
  </form>
  ▲▲▲ 非表示ここまで ▲▲▲ --}}
</div>

{{-- 一括操作ボタン --}}
<div class="flex items-center gap-2 mb-3">
  <form id="bulk-delete-form" action="{{ route('admin.reservations.destroySelected') }}" method="POST" class="flex items-center gap-2">
    @csrf
    @method('DELETE')
    <input type="hidden" name="ids" id="bulk-ids">
    <button type="submit" class="btn btn-error" onclick="return confirm('選択した予約を削除します。よろしいですか？');">
      選択削除
    </button>
  </form>

  <form action="{{ route('admin.reservations.destroyAll') }}" method="POST" onsubmit="return confirmDeleteAll();" class="inline">
    @csrf
    @method('DELETE')
    <button class="btn btn-outline btn-error">全件削除</button>
  </form>
</div>

<div class="overflow-x-auto">
  <table class="table table-zebra">
    <thead>
      <tr>
        <th class="w-10">
          <input type="checkbox" class="checkbox" id="select-all">
        </th>
        <th class="whitespace-nowrap">予約日</th>
        <th class="whitespace-nowrap">時間</th>
        <th class="whitespace-nowrap">受取</th>
        <th class="whitespace-nowrap">予約者氏名</th>
        <th class="whitespace-nowrap">選択商品</th>
        <th class="whitespace-nowrap text-right">合計金額</th>
        <th class="whitespace-nowrap text-right">操作</th>
      </tr>
    </thead>
    <tbody>
    @forelse ($reservations as $r)
      @php
        // ▼ スロット情報（slot_date: Carbon, start_time: "HH:MM:SS"）
        $slot   = $r->slot;
        $date   = $slot?->slot_date ? \Illuminate\Support\Carbon::parse($slot->slot_date)->format('Y-m-d') : '—';
        $time   = $slot?->start_time ? substr((string)$slot->start_time, 0, 5) : '—';

        // ▼ 受取方法はスロットの種別から（reservations.method は使わない）
        $slotType    = $slot->slot_type ?? null; // 'store'|'delivery'
        $methodLabel = match($slotType) {
          'store' => '店頭',
          'delivery' => '配達',
          default => '—',
        };

        // ▼ 合計金額（存在する情報でフォールバック）
        $amount = $r->total_amount
          ?? ((isset($r->unit_price, $r->quantity)) ? ((int)$r->unit_price * (int)$r->quantity) : null)
          ?? ($r->product->price ?? null);
      @endphp
      <tr>
        <td>
          <input type="checkbox" class="checkbox row-check" value="{{ $r->id }}">
        </td>
        <td>{{ $date }}</td>
        <td>{{ $time }}</td>
        <td>
          @if($slotType === 'store')
            <span class="badge badge-primary">店頭</span>
          @elseif($slotType === 'delivery')
            <span class="badge badge-secondary">配達</span>
          @else
            <span class="badge">—</span>
          @endif
        </td>
        <td>{{ $r->guest_name ?? $r->customer_name ?? '—' }}</td>
        <td>
          @if($r->product)
            <div class="flex items-center gap-2">
              <span class="badge">{{ \Illuminate\Support\Str::limit($r->product->name, 20) }}</span>
            </div>
          @else
            （商品未設定）
          @endif
        </td>
        <td class="text-right font-semibold">
          {{ isset($amount) ? number_format((int)$amount) . '円' : '—' }}
        </td>
        <td class="text-right">
          <form action="{{ route('admin.reservations.destroy', $r) }}" method="POST" onsubmit="return confirm('この予約を削除します。よろしいですか？');" class="inline">
            @csrf
            @method('DELETE')
            <button class="btn btn-sm btn-error">削除</button>
          </form>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="8" class="text-center text-gray-500">該当する予約はありません</td>
      </tr>
    @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">
  {{ $reservations->appends(request()->query())->links() }}
</div>

{{-- JS（最小限） --}}
<script>
  const selectAll = document.getElementById('select-all');
  const checks = () => Array.from(document.querySelectorAll('.row-check'));
  const bulkIds = document.getElementById('bulk-ids');
  const bulkForm = document.getElementById('bulk-delete-form');

  selectAll?.addEventListener('change', e => {
    checks().forEach(cb => cb.checked = e.target.checked);
    setBulkIds();
  });

  document.addEventListener('change', e => {
    if (e.target.classList?.contains('row-check')) setBulkIds();
  });

  function setBulkIds() {
    const ids = checks().filter(cb => cb.checked).map(cb => cb.value);
    bulkIds.value = ids.join(',');
  }

  function confirmDeleteAll() {
    if (!confirm('【危険】予約を全件削除します。よろしいですか？')) return false;
    return confirm('本当に全件削除しますか？この操作は取り消せません。');
  }
</script>
@endsection
