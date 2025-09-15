@extends('layouts.daisy')

@section('title','予約商品一覧')

@section('content')
<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-4">予約商品一覧</h1>

  @if(session('cart_error'))
    <div class="alert alert-error mb-4">{{ session('cart_error') }}</div>
  @endif
  @if(session('ok'))
    <div class="alert alert-success mb-4">{{ session('ok') }}</div>
  @endif

  @php
    $method = $meta['method'] ?? null;   // 'store' or 'delivery'
    $date   = $meta['date']   ?? null;
    $time   = $meta['time']   ?? null;
  @endphp

  <div class="mb-4 text-sm text-gray-600">
    <div>受取方法：<span class="font-semibold">{{ $method === 'delivery' ? '配送' : '店頭受取' }}</span></div>
    @if($date && $time)
      <div>受取日時：<span class="font-semibold">{{ $date }} {{ $time }}</span></div>
    @endif
  </div>

  @if(empty($cart))
    <div class="card bg-base-100 shadow p-6">
      <p>予約商品はまだありません。</p>
      <a href="{{ route('products.index') }}" class="btn btn-outline mt-4">商品を選ぶ</a>
    </div>
  @else
    <div class="overflow-x-auto">
      <table class="table">
        <thead>
          <tr>
            <th>商品名</th>
            <th class="text-right">単価</th>
            <th class="text-center">数量</th>
            <th class="text-right">小計</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($cart as $row)
          <tr>
            <td>{{ $row['name'] }}</td>
            <td class="text-right">{{ number_format($row['price']) }}円</td>
            <td class="text-center">
              <form method="POST" action="{{ route('cart.update', $row['row_id']) }}" class="inline-flex items-center gap-2">
                @csrf @method('PATCH')
                <input type="number" name="qty" value="{{ $row['qty'] }}" min="1" max="99" class="input input-bordered w-20">
                <button class="btn btn-sm">更新</button>
              </form>
            </td>
            <td class="text-right">{{ number_format($row['price'] * $row['qty']) }}円</td>
            <td class="text-right">
              <form method="POST" action="{{ route('cart.remove', $row['row_id']) }}">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-ghost">削除</button>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <th colspan="3" class="text-right">合計</th>
            <th class="text-right text-xl">{{ number_format($total) }}円</th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>

    @if($isDelivery && $total < 4000)
      <div class="alert alert-warning mt-4">
        配送は <span class="font-bold">4,000円（税込）以上</span> のお買い上げで承ります。現在の合計は {{ number_format($total) }}円 です。
      </div>
    @endif

    <div class="mt-6 flex flex-wrap gap-3">
      <a href="{{ route('products.index') }}" class="btn btn-outline">商品を追加</a>

      <form method="POST" action="{{ route('cart.clear') }}">
        @csrf @method('DELETE')
        <button class="btn btn-outline">すべて削除</button>
      </form>

      <a
        @class([
          'btn',
          'btn-primary' => $canProceed,
          'btn-disabled pointer-events-none opacity-50' => !$canProceed,
        ])
        href="{{ $canProceed ? route('checkout.shipping') : '#' }}"
      >
        次へ（配送先情報）
      </a>
    </div>
  @endif
</div>
@endsection
