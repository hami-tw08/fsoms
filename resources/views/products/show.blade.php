@extends('layouts.daisy')

@section('title', $product->name ?? '商品詳細')

@section('content')
<div class="container mx-auto p-4">
  {{-- パンくず --}}
  <div class="text-sm breadcrumbs mb-3">
    <ul>
      <li><a href="{{ route('products.index') }}">商品一覧</a></li>
      <li>{{ $product->name ?? '商品詳細' }}</li>
    </ul>
  </div>

  {{-- ▼ ステッパー（現在=2：商品の選択） --}}
  @php
    $__steps = ['予約日時・受取り方法','商品の選択','予約者情報の入力','入力情報の確認','予約完了'];
  @endphp
  <div class="overflow-x-auto mb-6" data-theme="namieflower">
    <x-stepper
      :steps="$__steps"
      :current="2"
      :verticalOnSm="false"
      :showLabels="true"
      class="justify-center w-full mx-auto max-w-4xl gap-3 min-w-max" />
  </div>
  {{-- ▲ ステッパーここまで --}}



  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- 画像 --}}
    <div>
      @php
        // 画像URLが無い場合はトップ画像を流用（任意のプレースホルダに差し替えてOK）
        $image = $product->image_url ?? asset('img/top-image.png');
      @endphp
      <div class="rounded-box overflow-hidden bg-base-200">
        <img src="{{ $image }}" alt="{{ $product->name }}" class="w-full h-80 object-cover">
      </div>
    </div>

    {{-- 詳細＆購入フォーム --}}
    <div>
      <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
      <div class="mt-2 text-xl font-semibold">{{ number_format($product->price) }}円 <span class="text-sm font-normal opacity-70">（税込）</span></div>

      @if(!empty($product->description))
        <div class="mt-4 prose max-w-none">
          <p>{{ $product->description }}</p>
        </div>
      @endif

      <form method="POST" action="{{ route('cart.add') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="name" value="{{ $product->name }}">
        <input type="hidden" name="price" value="{{ $product->price }}">

        <div class="form-control max-w-xs">
          <label class="label"><span class="label-text">数量</span></label>
          <input type="number" name="qty" value="{{ old('qty', 1) }}" min="1" max="99" class="input input-bordered">
        </div>

        <div class="flex flex-wrap gap-3">
          <button class="btn btn-primary">予約する</button>
          <a href="{{ route('cart.index') }}" class="btn btn-outline">予約商品一覧へ</a>
          <a href="{{ route('products.index') }}" class="btn btn-ghost">他の商品を見る</a>
        </div>

        <div class="text-xs text-gray-500">
          ※ 配送の場合、合計4,000円（税込）未満は次へ進めません（店頭受取は金額制限なし）
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
