@extends('layouts.daisy')

@section('title', '商品一覧')

@section('content')
<div data-theme="namieflower">
  <h1 class="text-2xl font-bold mb-2">商品を選択</h1>

{{-- ▼ daisyUI Steps（現在=2） --}}
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

<style>
  /* ベースは薄グレー（デフォルト紫を無効化） */
  [data-theme="namieflower"] ol.steps[data-current] .step::before,
  [data-theme="namieflower"] ol.steps[data-current] .step + .step::after {
    background:#e5e7eb !important; border-color:#e5e7eb !important;
  }
  /* ラベルは常に黒系 */
  [data-theme="namieflower"] ol.steps[data-current] .step,
  [data-theme="namieflower"] ol.steps[data-current] .step > span { color:#111827 !important; }

  /* ====== 5ステップ固定の強制配色 ====== */
  /* current=1 */
  [data-theme="namieflower"] ol.steps[data-current="1"] .step:nth-child(1)::before { background:#d493b5 !important; border-color:#d493b5 !important; }
  /* current=2 */
  [data-theme="namieflower"] ol.steps[data-current="2"] .step:nth-child(-n+1)::before { background:#679ace !important; border-color:#679ace !important; }
  [data-theme="namieflower"] ol.steps[data-current="2"] .step:nth-child(1) + .step::after { background:#679ace !important; border-color:#679ace !important; }
  [data-theme="namieflower"] ol.steps[data-current="2"] .step:nth-child(2)::before { background:#d493b5 !important; border-color:#d493b5 !important; }

  /* current=3 */
  [data-theme="namieflower"] ol.steps[data-current="3"] .step:nth-child(-n+2)::before { background:#679ace !important; border-color:#679ace !important; }
  [data-theme="namieflower"] ol.steps[data-current="3"] .step:nth-child(2) + .step::after { background:#679ace !important; border-color:#679ace !important; }
  [data-theme="namieflower"] ol.steps[data-current="3"] .step:nth-child(3)::before { background:#d493b5 !important; border-color:#d493b5 !important; }

  /* current=4 */
  [data-theme="namieflower"] ol.steps[data-current="4"] .step:nth-child(-n+3)::before { background:#679ace !important; border-color:#679ace !important; }
  [data-theme="namieflower"] ol.steps[data-current="4"] .step:nth-child(3) + .step::after { background:#679ace !important; border-color:#679ace !important; }
  [data-theme="namieflower"] ol.steps[data-current="4"] .step:nth-child(4)::before { background:#d493b5 !important; border-color:#d493b5 !important; }

  /* current=5 */
  [data-theme="namieflower"] ol.steps[data-current="5"] .step:nth-child(-n+4)::before { background:#679ace !important; border-color:#679ace !important; }
  [data-theme="namieflower"] ol.steps[data-current="5"] .step:nth-child(4) + .step::after { background:#679ace !important; border-color:#679ace !important; }
  [data-theme="namieflower"] ol.steps[data-current="5"] .step:nth-child(5)::before { background:#d493b5 !important; border-color:#d493b5 !important; }
</style>




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
