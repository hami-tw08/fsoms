@extends('layouts.admin')
@section('title','予約一覧')

@section('content')
<div class="flex items-center justify-between mb-4 gap-2">
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
</div>

<div class="overflow-x-auto">
  <table class="table table-zebra">
    <thead>
      <tr>
        <th class="whitespace-nowrap">予約日</th>
        <th class="whitespace-nowrap">時間</th>
        <th class="whitespace-nowrap">受取</th>
        <th class="whitespace-nowrap">予約者氏名</th>
        <th class="whitespace-nowrap">選択商品</th>
        <th class="whitespace-nowrap text-right">合計金額</th>
      </tr>
    </thead>
    <tbody>
    @forelse ($reservations as $r)
      @php
        // 予約日時はスロットのstart_atを優先
        $start = $r->slot?->start_at?->timezone(config('app.timezone'));
        $date  = $start?->format('Y-m-d') ?? '—';
        $time  = $start?->format('H:i') ?? '—';

        // 受取方法
        $methodLabel = match($r->method ?? null) {
          'store' => '店頭',
          'delivery' => '配達',
          default => '—',
        };

        // 合計金額（存在する情報でフォールバック）
        $amount = $r->total_amount
          ?? ((isset($r->unit_price, $r->quantity)) ? ((int)$r->unit_price * (int)$r->quantity) : null)
          ?? ($r->product->price ?? null);
      @endphp
      <tr>
        <td>{{ $date }}</td>
        <td>{{ $time }}</td>
        <td>
          @if(($r->method ?? null) === 'store')
            <span class="badge badge-primary">店頭</span>
          @elseif(($r->method ?? null) === 'delivery')
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
          {{ isset($amount) ? number_format($amount) . '円' : '—' }}
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="6" class="text-center text-gray-500">該当する予約はありません</td>
      </tr>
    @endforelse
    </tbody>
  </table>
</div>


<div class="mt-4">
  {{ $reservations->appends(request()->query())->links() }}
</div>
@endsection
