@extends('layouts.daisy')

@section('title', '商品一覧')

@section('content')
<div data-theme="namieflower">
  <h1 class="text-2xl font-bold mb-2">商品を選択</h1>

  {{-- ▼ daisyUI Steps（現在=2） --}}
  @php
    $__steps = ['予約日時・受取り方法','商品の選択','予約者情報の入力','入力情報の確認','予約完了'];
  @endphp
  <div class="overflow-x-auto mb-6">
    <x-stepper
      :steps="$__steps"
      :current="2"
      :verticalOnSm="false"
      :showLabels="true"
      class="justify-center w-full mx-auto max-w-4xl gap-3 min-w-max" />
  </div>

  @if($products->isEmpty())
    <div class="alert alert-warning">現在、販売中の商品はありません。</div>
  @else
    <div class="grid md:grid-cols-3 gap-6">
      @foreach($products as $p)
        <div class="card bg-base-100 shadow-xl">
          <figure class="aspect-[4/3] overflow-hidden">
            <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover">
          </figure>
          <div class="card-body">
            <h2 class="card-title">{{ $p->name }}</h2>
            @if($p->description)
              <p class="text-sm text-base-content/70">{{ Str::limit($p->description, 80) }}</p>
            @endif
            <div class="flex items-center justify-between mt-2">
              <span class="font-semibold">
                @if(method_exists($p, 'getPriceFormattedAttribute'))
                  {{ $p->price_formatted }}
                @else
                  {{ number_format($p->price) }}円
                @endif
              </span>
              <a href="{{ route('products.show', ['product' => $p->slug]) }}" class="btn btn-primary btn-sm">詳細へ</a>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection
