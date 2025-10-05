@extends('layouts.admin')
@section('title','予約一覧')

@section('content')
<div class="space-y-4">
  <h1 class="text-2xl font-bold">予約一覧</h1>

  {{-- フラッシュ/エラーは layouts.admin 側で表示済み --}}

  <div class="overflow-x-auto">
    <table class="table table-zebra w-full min-w-[1200px]">
      <thead>
        <tr>
          <th>ID</th>
          <th>予約日</th>
          <th>時間</th>
          <th>受取</th>
          <th>予約者氏名</th>
          <th>電話</th>
          <th>選択商品</th>
          <th>数量</th>
          <th>合計金額</th>

          {{-- ▼ shipping.blade.php で入力できる主な項目を列として表示 --}}
          <th>配送先氏名</th>
          <th>会社</th>
          <th>店舗</th>
          <th>エリア</th>
          <th>郵便</th>
          <th>住所</th>
          <th>備考</th>

          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($reservations as $r)
          @php
            $slot = $r->slot;
            $date = optional($slot?->slot_date) ? \Illuminate\Support\Carbon::parse($slot->slot_date)->format('Y-m-d') : '';
            $start = $slot?->start_time ? \Illuminate\Support\Str::of($slot->start_time)->limit(5,'') : '';
            $end   = $slot?->end_time   ? \Illuminate\Support\Str::of($slot->end_time)->limit(5,'')   : '';
            $method = $slot?->slot_type === 'delivery' ? '配送' : '店頭';

            // 予約者名は優先順：guest_name → customer.name → 'ー'
            $guestName  = $r->guest_name ?: ($r->customer->name ?? 'ー');
            $guestPhone = $r->guest_phone ?: ($r->customer->phone ?? 'ー');

            // shipping_json は array キャスト済み
            $sx = $r->shipping_json ?? [];
          @endphp
          <tr>
            <td class="whitespace-nowrap">{{ $r->id }}</td>
            <td class="whitespace-nowrap">{{ $date }}</td>
            <td class="whitespace-nowrap">{{ $start }}{{ $end ? ' - '.$end : '' }}</td>
            <td class="whitespace-nowrap">
              <span class="badge">{{ $method }}</span>
            </td>
            <td class="whitespace-nowrap">{{ $guestName }}</td>
            <td class="whitespace-nowrap">{{ $guestPhone }}</td>
            <td class="whitespace-nowrap">
              {{ $r->product?->name ?? '—' }}
            </td>
            <td class="whitespace-nowrap text-right">{{ $r->quantity }}</td>
            <td class="whitespace-nowrap text-right">{{ number_format((int)$r->total_amount) }}円</td>

            {{-- ▼ shipping 情報 --}}
            <td class="whitespace-nowrap">{{ $sx['recipient_name'] ?? '—' }}</td>
            <td class="whitespace-nowrap">{{ $sx['recipient_company'] ?? '—' }}</td>
            <td class="whitespace-nowrap">{{ $sx['recipient_store'] ?? '—' }}</td>
            <td class="whitespace-nowrap">
              {{-- DBのdelivery_area(enum)は英字、見せるのは日本語優先 --}}
              {{ $sx['area_jp'] ?? match($r->delivery_area){
                  'namie'=>'浪江','futaba'=>'双葉','okuma'=>'大熊','odaka'=>'小高区', default => '—'
              } }}
            </td>
            <td class="whitespace-nowrap">{{ $sx['postal_code'] ?? ($r->delivery_postal_code ?? '—') }}</td>
            <td class="whitespace-nowrap">{{ $sx['address'] ?? ($r->delivery_address ?? '—') }}</td>
            <td class="max-w-[280px]">
              <div class="truncate" title="{{ $sx['notes'] ?? $r->notes }}">
                {{ $sx['notes'] ?? $r->notes ?? '—' }}
              </div>
            </td>

            <td class="whitespace-nowrap">
              <a href="{{ route('admin.reservations.show', $r) }}" class="btn btn-xs">詳細</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div>
    {{ $reservations->links() }}
  </div>
</div>
@endsection
