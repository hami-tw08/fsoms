@extends('layouts.daisy')

@section('title', '商品一覧')

@section('content')
<div data-theme="namieflower">
  <h1 class="text-2xl font-bold mb-2">商品を選択</h1>

{{-- ▼ ステッパー（現在=2） --}}
@php
  $steps = [
    1 => '予約日時等を指定する',
    2 => '商品を選択する',
    3 => '注文者情報等を入力する',
    4 => '登録する情報を確認する',
    5 => 'ご予約完了',
  ];
  $current = 2; // いまの画面
@endphp

<div class="overflow-x-auto mb-6" data-theme="namieflower">
  <ul class="steps steps-horizontal justify-center w-full mx-auto max-w-4xl gap-3 min-w-max">
    @foreach ($steps as $i => $label)
      @php $isDone = $i <= $current; @endphp
      <li class="{{ $isDone ? 'step step-primary' : 'step' }} relative shrink-0">
        {{-- 戻りリンク：1へは不可。2の画面なので i<2 は「1」しか該当しない＝リンクなし --}}
        @if ($i < $current && $i !== 1)
          <a
            href="{{ $i === 2 ? route('products.index') : '#' }}"
            class="absolute inset-0 z-[2] block"
            aria-label="{{ $label }}"></a>
        @endif
        <span class="mt-1 block text-[11px] md:text-sm leading-tight max-w-[9rem] mx-auto pointer-events-none relative z-[1] {{ $i === $current ? 'font-semibold' : '' }}">
          {{ $label }}
        </span>
      </li>
    @endforeach
  </ul>
</div>

{{-- ▼ 現在の選択（受取方法・受取日時） --}}
<div class="mb-4 text-sm text-gray-600">
  <div>（現在の選択）受取方法：
    <span class="font-semibold">
       {{ ['delivery' => '配送', 'store' => '店頭受取'][$meta['method'] ?? ''] ?? '未選択' }}
    </span>
  </div>

    @php
    $date = $meta['date'] ?? null;
    $time = $meta['time'] ?? null;
    @endphp

  @if($date && $time)
    <div>受取日時：<span class="font-semibold">{{ $date }} {{ $time }}</span></div>
  @elseif($date)
    <div>受取日：<span class="font-semibold">{{ $date }}</span></div>
  @elseif($time)
    <div>希望受取り時間：<span class="font-semibold">{{ $time }}</span></div>
  @endif
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

  @if(session('reservation.flow_locked'))
  <div class="alert alert-warning mb-4 text-sm">
    現在の予約フローが進行中です。別日を選ぶ場合は、<b>「ご予約完了」画面の「別の日程で新しく予約する」</b>からお願いします。途中で戻るとカート内容が失われる可能性があります。
  </div>
  @endif

</div>
@endsection
