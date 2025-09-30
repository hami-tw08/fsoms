@extends('layouts.daisy')

@section('title','完了')

@section('content')
<div class="container mx-auto p-4">

  {{-- ▼ ステッパー（現在=5：予約完了） --}}
  @php
    $__steps = ['予約日時・受取り方法','商品の選択','予約者情報の入力','入力情報の確認','予約完了'];
  @endphp
  <div class="overflow-x-auto mb-4" data-theme="namieflower">
    <x-stepper
      :steps="$__steps"
      :current="5"
      :verticalOnSm="false"
      :showLabels="true"
      class="justify-center w-full mx-auto max-w-4xl gap-3 min-w-max" />
  </div>
  {{-- ▲ ステッパーここまで --}}

  {{-- 完了メッセージ --}}
  <div class="card bg-base-100 shadow p-8 mb-6">
    @if(session('ok'))
      <div class="alert alert-success mb-4">{{ session('ok') }}</div>
    @endif
    <h1 class="text-2xl font-bold mb-3">ご予約ありがとうございます</h1>
    <p class="text-sm">
      次のとおりご予約を承りました。予約内容の変更や、キャンセルをご希望の場合はメールまたはお電話でお知らせください。<br>
      メールアドレス：<span class="font-semibold">test@example.com</span>　
      電話：<span class="font-semibold">080-xxxx-xxxx</span>
    </p>
    <div class="mt-6">
      <a href="{{ route('reserve.create') }}" class="btn btn-primary">トップページへ</a>
    </div>
  </div>

  @php
    // Controller から渡来 or フォールバック（reservation.completed）
    $completed = [
      'cart' => $cart ?? (session('reservation.completed.cart') ?? []),
      'meta' => $meta ?? (session('reservation.completed.meta') ?? []),
      'deliveryArea' => $deliveryArea ?? (session('reservation.completed.deliveryArea') ?? null),
      'total' => $total ?? (session('reservation.completed.total') ?? 0),
      'deliveryFee' => $deliveryFee ?? (session('reservation.completed.deliveryFee') ?? 0),
      'grandTotal' => $grandTotal ?? (session('reservation.completed.grandTotal') ?? 0),
    ];

    $cart = (array)($completed['cart'] ?? []);
    $meta = (array)($completed['meta'] ?? []);
    $deliveryArea = $completed['deliveryArea'] ?? null;

    $method = $meta['method'] ?? null; // 'store' or 'delivery'
    $isDelivery = $method === 'delivery';
    $date = $meta['date'] ?? null;
    $time = $meta['time'] ?? null;

    $total = (int)($completed['total'] ?? 0);
    $deliveryFee = (int)($completed['deliveryFee'] ?? 0);
    $grandTotal = (int)($completed['grandTotal'] ?? ($total + $deliveryFee));

    $methodLabel = $isDelivery ? '配送' : '店頭受取';
  @endphp

  {{-- 受取方法・日時（連絡先は表示しない） --}}
  <div class="card bg-base-100 shadow p-4 mb-6">
    <div class="grid md:grid-cols-3 gap-2 text-sm">
      <div>受取方法：<span class="font-semibold">{{ $methodLabel }}</span></div>
      @if($date && $time)
        <div>受取日時：<span class="font-semibold">{{ $date }} {{ $time }}</span></div>
      @endif
      @if($isDelivery && !empty($deliveryArea))
        <div>配送エリア：<span class="font-semibold">{{ $deliveryArea }}</span></div>
      @endif
    </div>
  </div>

  {{-- ご注文内容 --}}
  <div class="card bg-base-100 border shadow mb-6">
    <div class="card-body p-4 overflow-x-auto">
      <h2 class="text-lg font-semibold mb-3">ご注文内容</h2>

      @if(!empty($cart))
        <table class="table table-zebra">
          <thead class="font-semibold">
            <tr>
              <th>商品名</th>
              <th class="text-right">単価</th>
              <th class="text-center">数量</th>
              <th class="text-right">小計</th>
            </tr>
          </thead>
          <tbody>
          @foreach($cart as $row)
            <tr>
              <td>{{ $row['name'] ?? '' }}</td>
              <td class="text-right">{{ number_format((int)($row['price'] ?? 0)) }}円</td>
              <td class="text-center">{{ (int)($row['qty'] ?? 0) }}</td>
              <td class="text-right">{{ number_format((int)($row['price'] ?? 0) * (int)($row['qty'] ?? 0)) }}円</td>
            </tr>
          @endforeach
          </tbody>
          <tfoot class="font-semibold">
            <tr>
              <th colspan="3" class="text-right">小計</th>
              <th class="text-right">{{ number_format($total) }}円</th>
            </tr>
            @if($isDelivery)
            <tr>
              <th colspan="3" class="text-right">配送料</th>
              <th class="text-right">{{ $deliveryFee === 0 ? '無料' : number_format($deliveryFee).'円' }}</th>
            </tr>
            @endif
            <tr class="font-bold">
              <th colspan="3" class="text-right text-lg">合計</th>
              <th class="text-right text-xl">{{ number_format($grandTotal) }}円</th>
            </tr>
          </tfoot>
        </table>

        <div class="mt-2 text-sm font-bold">
          お代はお渡し時に頂戴します
        </div>
      @else
        <div class="alert">表示できるご注文情報が見つかりませんでした。</div>
      @endif
    </div>
  </div>

</div>
@endsection
