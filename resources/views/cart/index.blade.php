@extends('layouts.daisy')

@section('title', 'カート')

@section('content')
<div class="container mx-auto p-4">
  {{-- パンくず --}}
  <div class="text-sm breadcrumbs mb-3">
    <ul>
      <li><a href="{{ route('products.index') }}">商品一覧</a></li>
      <li>カート</li>
    </ul>
  </div>

  {{-- ▼ ステッパー（現在=2：商品を選択/カート） --}}
  <div class="overflow-x-auto mb-6" data-theme="namieflower">
    <ul class="steps steps-horizontal justify-center w-full mx-auto max-w-4xl gap-3 min-w-max">
      {{-- 1：戻り禁止（リンクなし） --}}
      <li class="step step-primary relative shrink-0">
        <span class="mt-1 block text-[11px] md:text-sm leading-tight max-w-[9rem] mx-auto pointer-events-none relative z-[1]">
          予約日時等を指定する
        </span>
      </li>

      {{-- 2：現ステップ（必要なら商品一覧へ戻るリンクを張る） --}}
      <li class="step step-primary relative shrink-0">
        {{-- 2は「同じ工程」への導線として商品一覧へ戻るリンクを付与してもOK --}}
        <a href="{{ route('products.index') }}" class="absolute inset-0 z-[2] block" aria-label="商品を選択する"></a>
        <span class="mt-1 block text-[11px] md:text-sm leading-tight max-w-[9rem] mx-auto pointer-events-none relative z-[1] font-semibold">
          商品を選択する
        </span>
      </li>

      <li class="step relative shrink-0">
        <span class="mt-1 block text-[11px] md:text-sm leading-tight max-w-[9rem] mx-auto pointer-events-none relative z-[1]">
          注文者情報等を入力する
        </span>
      </li>
      <li class="step relative shrink-0">
        <span class="mt-1 block text-[11px] md:text-sm leading-tight max-w-[9rem] mx-auto pointer-events-none relative z-[1]">
          登録する情報を確認する
        </span>
      </li>
      <li class="step relative shrink-0">
        <span class="mt-1 block text-[11px] md:text-sm leading-tight max-w-[9rem] mx-auto pointer-events-none relative z-[1]">
          ご予約完了
        </span>
      </li>
    </ul>
  </div>
  {{-- ▲ ステッパーここまで --}}


  @if(session('cart_error'))
    <div class="alert alert-error">{{ session('cart_error') }}</div>
  @endif
  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif

  {{-- グループが無ければ空表示 --}}
  @if(empty($groups))
    <div class="card bg-base-100 shadow p-6">
      <p>予約商品はまだありません。</p>
      <a href="{{ route('products.index') }}" class="btn btn-outline mt-4">商品を選ぶ</a>
    </div>
  @else
    {{-- 受取メタの参考（現在のセッション状態） --}}
    @php $method = $meta['method'] ?? null; $date = $meta['date'] ?? null; $time = $meta['time'] ?? null; @endphp
    <div class="text-sm text-gray-600">
      <div>（現在の選択）受取方法：<span class="font-semibold">{{ $method === 'delivery' ? '配送' : ($method === 'store' ? '店頭受取' : '未選択') }}</span></div>
      @if($date && $time)
        <div>受取日時：<span class="font-semibold">{{ $date }} {{ $time }}</span></div>
      @endif
    </div>

    {{-- グループごとの表示 --}}
    @foreach($groups as $g)
      <div class="card bg-base-100 shadow">
        <div class="card-body">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-sm breadcrumbs">
              <ul>
                <li>受取方法：<span class="font-semibold">
                  @if($g['method'] === 'delivery') 配送
                  @elseif($g['method'] === 'store') 店頭受取
                  @else 未設定
                  @endif
                </span></li>
                <li>受取日時：<span class="font-semibold">
                  {{ $g['date'] ?? '未設定' }} {{ $g['time'] ?? '' }}
                </span></li>
              </ul>
            </div>

            <div class="text-right">
              <span class="opacity-70 text-sm">グループ合計</span>
              <div class="text-xl font-bold">{{ number_format($g['total']) }}円</div>
            </div>
          </div>

          <div class="overflow-x-auto mt-2">
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
                @foreach($g['items'] as $row)
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
            </table>
          </div>

          {{-- 配送の最低金額案内 --}}
          @if($g['isDelivery'] && !$g['canProceed'])
            <div class="alert alert-warning mt-3">
              配送は <span class="font-bold">4,000円（税込）以上</span> のお買い上げで承ります。現在の合計は {{ number_format($g['total']) }}円 です。
            </div>
          @endif

          <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ route('products.index') }}" class="btn btn-outline">商品を追加</a>

            {{-- 全削除はカート全体だが、UX的にはグループのみ削除も将来追加可 --}}
            <form method="POST" action="{{ route('cart.clear') }}">
              @csrf @method('DELETE')
              <button class="btn btn-outline">すべて削除</button>
            </form>

            {{-- グループ単位で次へ進む（必要なら ?k=meta_key を渡して識別できるように） --}}
            @php $nextUrl = $g['canProceed'] ? route('checkout.shipping', ['k' => $g['meta_key']]) : '#'; @endphp
            <a
              href="{{ $nextUrl }}"
              @class([
                'btn',
                'btn-primary' => $g['canProceed'],
                'btn-disabled pointer-events-none opacity-50' => !$g['canProceed'],
              ])
            >
              次へ（注文者情報等の入力）
            </a>
          </div>
        </div>
      </div>
    @endforeach
  @endif
</div>
@endsection
