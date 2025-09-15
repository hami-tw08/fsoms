@extends('layouts.daisy')

@php
  // タイトルや見出しを動的に
  $meta = $meta ?? session('reservation.meta', []);
  $isDelivery = ($isDelivery ?? null) ?? (($meta['method'] ?? null) === 'delivery');
  $cart = $cart ?? session('reservation.cart', []);
  $total = $total ?? collect($cart)->sum(fn($r) => (int)$r['price'] * (int)$r['qty']);
  $pageTitle = $isDelivery ? '配送先情報' : '予約者情報の登録';
  $ordererHeading = $isDelivery ? '注文者情報' : 'お名前、連絡先';
@endphp

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-4">{{ $pageTitle }}</h1>

  <div class="mb-4 text-sm text-gray-600">
    <div>受取方法：<span class="font-semibold">{{ $isDelivery ? '配送' : '店頭受取' }}</span></div>
    @if(($meta['date'] ?? false) && ($meta['time'] ?? false))
      <div>受取日時：<span class="font-semibold">{{ $meta['date'] }} {{ $meta['time'] }}</span></div>
    @endif
  </div>

  @if(session('cart_error'))
    <div class="alert alert-error mb-4">{{ session('cart_error') }}</div>
  @endif

  @if ($errors->any())
    <div class="alert alert-warning mb-4">
      入力内容に不備があります。各項目の下のエラーメッセージをご確認ください。
    </div>
  @endif

  <form method="POST" action="{{ route('checkout.shipping.store') }}" class="grid gap-6 md:grid-cols-2">
    @csrf

    {{-- 注文者（店頭受取はここだけ表示／配送時も必須） --}}
    <div class="card bg-base-100 shadow p-6 md:col-span-2">
      <h2 class="font-semibold mb-3">{{ $ordererHeading }}</h2>
      <div class="grid gap-3 md:grid-cols-2">
        <div>
          <label class="label">氏名<span class="text-error">*</span></label>
          <input
            type="text"
            name="orderer_name"
            value="{{ old('orderer_name') }}"
            class="input input-bordered w-full @error('orderer_name') input-error @enderror"
            @error('orderer_name') aria-invalid="true" @enderror
          >
          @if($errors->has('orderer_name'))
            <div class="text-error text-sm mt-1">氏名は必須です。</div>
          @endif
        </div>
        <div>
          <label class="label">電話番号<span class="text-error">*</span></label>
          <input
            type="text"
            name="orderer_phone"
            value="{{ old('orderer_phone') }}"
            class="input input-bordered w-full @error('orderer_phone') input-error @enderror"
            @error('orderer_phone') aria-invalid="true" @enderror
          >
          @if($errors->has('orderer_phone'))
            <div class="text-error text-sm mt-1">電話番号は必須です。</div>
          @endif
        </div>
      </div>
    </div>

    {{-- 配送先（配送のときだけ表示） --}}
    @if($isDelivery)
      <div class="card bg-base-100 shadow p-6 md:col-span-2">
        <h2 class="font-semibold mb-3">配送先</h2>
        <div class="grid gap-3 md:grid-cols-2">
          <div>
            <label class="label">配送先氏名 <span class="text-error">*</span></label>
            <input
              type="text"
              name="recipient_name"
              value="{{ old('recipient_name') }}"
              class="input input-bordered w-full @error('recipient_name') input-error @enderror"
              @error('recipient_name') aria-invalid="true" @enderror
            >
            @if($errors->has('recipient_name'))
              <div class="text-error text-sm mt-1">配送先の氏名は必須です。</div>
            @endif
          </div>
          <div>
            <label class="label">会社名</label>
            <input
              type="text"
              name="recipient_company"
              value="{{ old('recipient_company') }}"
              class="input input-bordered w-full"
            >
          </div>
          <div>
            <label class="label">店舗名</label>
            <input
              type="text"
              name="recipient_store"
              value="{{ old('recipient_store') }}"
              class="input input-bordered w-full"
            >
          </div>

          <div>
            <label class="label">配送エリア<span class="text-error">*</span></label>
            <select
              name="area"
              class="select select-bordered w-full @error('area') select-error @enderror"
              @error('area') aria-invalid="true" @enderror
            >
              <option value="">選択してください</option>
              @foreach(['浪江','双葉','大熊','小高区'] as $area)
                <option value="{{ $area }}" @selected(old('area')===$area)>{{ $area }}</option>
              @endforeach
            </select>
            @if($errors->has('area'))
              <div class="text-error text-sm mt-1">配送エリアを選択してください。</div>
            @endif
          </div>
          <div class="md:col-span-2">
            <label class="label">住所<span class="text-error">*</span></label>
            <input
              type="text"
              name="address"
              value="{{ old('address') }}"
              placeholder="例）福島県双葉郡浪江町〇〇〇-〇"
              class="input input-bordered w-full @error('address') input-error @enderror"
              @error('address') aria-invalid="true" @enderror
            >
            @if($errors->has('address'))
              <div class="text-error text-sm mt-1">住所は必須です。</div>
            @endif
          </div>
        </div>
      </div>
    @endif

    <div class="md:col-span-2 flex gap-3">
      <a href="{{ route('cart.index') }}" class="btn btn-outline">戻る（予約商品一覧）</a>
      <button class="btn btn-primary">保存して進む（最終確認へ）</button>
    </div>
  </form>

  @if($isDelivery)
    <p class="mt-3 text-xs text-gray-500">＊配送は4,000円（税込）以上のお買い上げで承ります。</p>
  @endif
</div>
@endsection
