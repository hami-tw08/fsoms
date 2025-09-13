@extends('layouts.app')

@section('title', $product->name)

@section('content')
  <div class="grid md:grid-cols-2 gap-8">
    <div class="card bg-base-100 shadow-xl">
      <figure class="aspect-[4/3]">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
      </figure>
    </div>

    <div>
      <h1 class="text-3xl font-bold">{{ $product->name }}</h1>
      <div class="mt-2 text-xl">{{ $product->price_formatted }}</div>
      <p class="mt-4 leading-relaxed">{{ $product->description }}</p>

      <div class="mt-6 flex gap-3">
        <!-- 予約導線（後で予約フローに接続） -->
        <a href="{{ url('/reservations/create?product='.$product->slug) }}" class="btn btn-primary">この商品を予約する</a>
        <a href="{{ route('products.index') }}" class="btn">一覧へ戻る</a>
      </div>

      <div class="mt-8">
        <details class="collapse bg-base-100 border">
          <summary class="collapse-title text-md font-medium">商品について</summary>
          <div class="collapse-content text-sm text-base-content/80">
            <ul class="list-disc ml-5 space-y-1">
              <li>花材は仕入れ状況により一部変更になる場合があります</li>
              <li>色味指定がある場合は予約時の備考でご相談ください</li>
              <li>店頭受取または指定エリア配達（後続フローで選択）</li>
            </ul>
          </div>
        </details>
      </div>
    </div>
  </div>
@endsection
