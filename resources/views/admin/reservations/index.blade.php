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
        <th class="whitespace-nowrap">ID</th>
        <th class="whitespace-nowrap">日時</th>
        <th class="whitespace-nowrap">枠ID</th>
        <th class="whitespace-nowrap">氏名</th>
        <th class="whitespace-nowrap">電話</th>
        <th class="whitespace-nowrap">商品</th>
        <th class="whitespace-nowrap">受取/配達</th>
        <th class="whitespace-nowrap">エリア</th>
        <th class="whitespace-nowrap">作成</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    @forelse ($reservations as $r)
      <tr>
        <td>{{ $r->id }}</td>
        <td class="whitespace-nowrap">
          @if($r->slot)
            <div>{{ $r->slot->start_at?->format('Y-m-d H:i') }}〜</div>
          @else
            <span class="text-gray-400">未割当</span>
          @endif
        </td>
        <td>{{ $r->slot_id ?? '-' }}</td>
        <td>{{ $r->guest_name ?? '-' }}</td>
        <td><a href="tel:{{ $r->guest_phone }}">{{ $r->guest_phone ?? '-' }}</a></td>
        <td>
          @if($r->product)
            <div class="flex items-center gap-2">
              <span class="font-medium">#{{ $r->product_id }}</span>
              <span class="badge">{{ \Illuminate\Support\Str::limit($r->product->name, 16) }}</span>
            </div>
          @else
            {{ $r->product_id ?? '-' }}
          @endif
        </td>
        <td>
          @if(($r->method ?? null) === 'store')
            <span class="badge badge-primary">店頭</span>
          @elseif(($r->method ?? null) === 'delivery')
            <span class="badge badge-secondary">配達</span>
          @else
            <span class="badge">-</span>
          @endif
        </td>
        <td>{{ $r->delivery_area ?? '-' }}</td>
        <td class="whitespace-nowrap">{{ $r->created_at?->format('Y-m-d H:i') }}</td>
        <td class="text-right">
          <div class="flex justify-end gap-2">
            <a class="btn btn-xs" href="{{ route('admin.reservations.show', $r) }}">詳細</a>
            {{-- 必要なら編集機能 --}}
            {{-- <a class="btn btn-xs btn-outline" href="{{ route('admin.reservations.edit', $r) }}">編集</a> --}}
          </div>
        </td>
      </tr>
    @empty
      <tr><td colspan="10" class="text-center text-gray-500">該当する予約はありません</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">
  {{ $reservations->appends(request()->query())->links() }}
</div>
@endsection
