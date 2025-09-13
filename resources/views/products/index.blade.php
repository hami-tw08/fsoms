@extends('layouts.app')

@section('title', '商品一覧')

@section('content')
  <h1 class="text-2xl font-bold mb-4">商品を選択</h1>

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
              <a href="{{ route('products.show', $p) }}" class="btn btn-primary btn-sm">詳細へ</a>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
@endsection
