@extends('layouts.daisy')

@section('title','最終確認')

@if(session('cart_error'))
  <div class="alert alert-error mb-4">
    {{ session('cart_error') }}
  </div>
@endif

@section('content')
<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-4">最終確認</h1>

  {{-- ▼ ステッパー（現在=4：入力情報の確認） --}}
  @php
    $__steps = ['予約日時・受取り方法','商品の選択','予約者情報の入力','入力情報の確認','予約完了'];
  @endphp
  <div class="overflow-x-auto mb-4" data-theme="namieflower">
    <x-stepper
      :steps="$__steps"
      :current="4"
      :verticalOnSm="false"
      :showLabels="true"
      class="justify-center w-full mx-auto max-w-4xl gap-3 min-w-max" />
  </div>
  {{-- ▲ ステッパーここまで --}}

  {{-- パンくず（任意） --}}
  <div class="breadcrumbs text-sm mb-4">
    <ul>
      <li><a href="{{ route('cart.index') }}">カート</a></li>
      <li><a href="{{ route('checkout.shipping') }}">配送先情報</a></li>
      <li class="font-semibold">最終確認</li>
    </ul>
  </div>

  {{-- 受取方法・日時 --}}
  @php
    $methodLabel = $isDelivery ? '配送' : '店頭受取';
  @endphp
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

  {{-- ご連絡先 / 配送先 --}}
  <div class="card bg-base-100 shadow p-4 mb-6">
    <h2 class="text-lg font-semibold mb-3">ご連絡先{{ $isDelivery ? '・お届け先' : '' }}</h2>
    <div class="grid md:grid-cols-2 gap-4 text-sm">
      <div>
        <div>お名前：<span class="font-semibold">{{ $shipping['guest_name'] ?? '' }}</span></div>
        <div>電話番号：<span class="font-semibold">{{ $shipping['guest_phone'] ?? '' }}</span></div>
      </div>
      @if($isDelivery)
      <div>
        <div>郵便番号：<span class="font-semibold">{{ $shipping['delivery_postal_code'] ?? '' }}</span></div>
        <div>住所：<span class="font-semibold">{{ $shipping['delivery_address'] ?? '' }}</span></div>
      </div>
      @endif
    </div>
    @if(!empty($shipping['notes']))
      <div class="mt-3 text-sm">
        連絡事項：<span class="font-semibold whitespace-pre-line">{{ $shipping['notes'] }}</span>
      </div>
    @endif

    <div class="mt-4">
      <a href="{{ route('checkout.shipping') }}" class="btn btn-outline btn-sm">配送先情報を修正</a>
    </div>
  </div>

  {{-- カート内容（読み取り専用） --}}
  <div class="card bg-base-100 border shadow mb-6">
    <div class="card-body p-4 overflow-x-auto">
      <h2 class="text-lg font-semibold mb-3">ご注文内容</h2>

      @if(!empty($cart) && count($cart) > 0)
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
            <td>{{ $row['name'] }}</td>
            <td class="text-right">{{ number_format($row['price']) }}円</td>
            <td class="text-center">{{ $row['qty'] }}</td>
            <td class="text-right">{{ number_format($row['price'] * $row['qty']) }}円</td>
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
          {{-- 合計行を太字に --}}
          <tr class="font-bold">
            <th colspan="3" class="text-right text-lg">合計</th>
            <th class="text-right text-xl">{{ number_format($grandTotal) }}円</th>
          </tr>
        </tfoot>
      </table>

      {{-- 合計行のすぐ下に注意書き（太字） --}}
      <div class="mt-2 text-sm font-bold">
        お代はお渡し時に頂戴します
      </div>
      @else
        <div class="alert">
          <span>カートが空です。<a class="link" href="{{ route('cart.index') }}">カートへ戻る</a></span>
        </div>
      @endif
    </div>
  </div>

  {{-- 確定（同意チェックなし） --}}
  <form method="POST" action="{{ route('checkout.place') }}" class="card bg-base-100 border shadow p-6">
    @csrf
    <div class="flex flex-wrap gap-3">
      <a href="{{ route('cart.index') }}" class="btn btn-outline">カートに戻る</a>
      <button type="submit" class="btn btn-primary">この内容で確定する</button>
    </div>
  </form>
</div>
@endsection
