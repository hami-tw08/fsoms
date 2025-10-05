{{-- resources/views/admin/reservations/show.blade.php --}}
@extends('layouts.admin')
@section('title', '予約詳細')

@section('content')
<div class="space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">予約詳細 #{{ $reservation->id }}</h1>
    <div class="flex gap-2">
      <a href="{{ route('admin.reservations.index') }}" class="btn">一覧へ戻る</a>

      {{-- 危険操作：削除（任意） --}}
      <form method="POST" action="{{ route('admin.reservations.destroy', $reservation) }}"
            onsubmit="return confirm('この予約を削除しますか？');">
        @csrf @method('DELETE')
        <button class="btn btn-error">削除</button>
      </form>
    </div>
  </div>

  @php
    $slot = $reservation->slot;
    $date = optional($slot?->slot_date) ? \Illuminate\Support\Carbon::parse($slot->slot_date)->format('Y-m-d') : '—';
    $start = $slot?->start_time ? \Illuminate\Support\Str::of($slot->start_time)->limit(5,'') : '';
    $end   = $slot?->end_time   ? \Illuminate\Support\Str::of($slot->end_time)->limit(5,'')   : '';
    $methodJp = $slot?->slot_type === 'delivery' ? '配送' : '店頭';

    // 予約者表示（guest -> customer の順でフォールバック）
    $guestName  = $reservation->guest_name ?: ($reservation->customer->name  ?? '—');
    $guestPhone = $reservation->guest_phone ?: ($reservation->customer->phone ?? '—');

    // shipping_json（arrayキャスト済）
    $sx = $reservation->shipping_json ?? [];

    // 配送エリア（日本語優先、なければenum→日本語に変換）
    $areaJp = $sx['area_jp'] ?? match($reservation->delivery_area){
      'namie'=>'浪江','futaba'=>'双葉','okuma'=>'大熊','odaka'=>'小高区', default => null
    };
  @endphp

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    {{-- 予約概要 --}}
    <div class="card bg-base-100 shadow">
      <div class="card-body">
        <h2 class="card-title">概要</h2>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="opacity-70">予約ID</span><span>#{{ $reservation->id }}</span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">ステータス</span><span><span class="badge">{{ $reservation->status ?? '—' }}</span></span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">受取方法</span><span>{{ $methodJp }}</span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">受取日</span><span>{{ $date }}</span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">受取時間</span><span>{{ $start }}{{ $end ? ' - '.$end : '' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">作成日時</span><span>{{ optional($reservation->created_at)?->format('Y-m-d H:i') }}</span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">更新日時</span><span>{{ optional($reservation->updated_at)?->format('Y-m-d H:i') }}</span>
          </div>
        </div>
      </div>
    </div>

    {{-- 予約者（注文者） --}}
    <div class="card bg-base-100 shadow">
      <div class="card-body">
        <h2 class="card-title">予約者</h2>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="opacity-70">氏名</span><span>{{ $guestName }}</span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">電話</span><span>{{ $guestPhone }}</span>
          </div>

          {{-- shipping入力があれば優先表示（storeでも保存している想定） --}}
          @if(!empty($sx))
            <div class="divider my-2"></div>
            <div class="flex justify-between">
              <span class="opacity-70">注文者氏名</span><span>{{ $sx['orderer_name'] ?? '—' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="opacity-70">注文者電話</span><span>{{ $sx['orderer_phone'] ?? '—' }}</span>
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- 配送情報 --}}
    <div class="card bg-base-100 shadow">
      <div class="card-body">
        <h2 class="card-title">配送情報</h2>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="opacity-70">お届け先氏名</span><span>{{ $sx['recipient_name'] ?? '—' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">会社名</span><span>{{ $sx['recipient_company'] ?? '—' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">店舗名</span><span>{{ $sx['recipient_store'] ?? '—' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">配送エリア</span><span>{{ $areaJp ?? '—' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="opacity-70">郵便番号</span><span>{{ $sx['postal_code'] ?? ($reservation->delivery_postal_code ?? '—') }}</span>
          </div>
          <div class="text-sm">
            <div class="opacity-70">住所</div>
            <div class="whitespace-pre-wrap break-words">
              {{ $sx['address'] ?? ($reservation->delivery_address ?? '—') }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- 商品・金額 --}}
  <div class="card bg-base-100 shadow">
    <div class="card-body">
      <h2 class="card-title">商品・金額</h2>

      <div class="overflow-x-auto">
        <table class="table w-full min-w-[800px]">
          <thead>
            <tr>
              <th>商品</th>
              <th class="text-right">数量</th>
              <th class="text-right">合計</th>
            </tr>
          </thead>
          <tbody>
            {{-- スロット直結型（単一商品） --}}
            <tr>
              <td class="whitespace-nowrap">{{ $reservation->product?->name ?? '—' }}</td>
              <td class="whitespace-nowrap text-right">{{ $reservation->quantity ?? '—' }}</td>
              <td class="whitespace-nowrap text-right">{{ number_format((int)($reservation->total_amount ?? 0)) }}円</td>
            </tr>

            {{-- 将来：注文型（明細）にも対応するなら以下を利用
            @foreach($reservation->items as $it)
              <tr>
                <td>{{ $it->product?->name ?? '—' }}</td>
                <td class="text-right">{{ $it->quantity }}</td>
                <td class="text-right">{{ number_format((int)$it->quantity * (int)$it->unit_price) }}円</td>
              </tr>
            @endforeach
            --}}
          </tbody>
          <tfoot>
            <tr>
              <th class="text-right" colspan="2">合計</th>
              <th class="text-right">{{ number_format($reservation->total_amount_normalized) }}円</th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  {{-- 備考 --}}
  <div class="card bg-base-100 shadow">
    <div class="card-body">
      <h2 class="card-title">備考</h2>
      <div class="prose max-w-none">
        <p class="whitespace-pre-wrap break-words">{{ $sx['notes'] ?? ($reservation->notes ?? '—') }}</p>
      </div>
    </div>
  </div>
</div>
@endsection
