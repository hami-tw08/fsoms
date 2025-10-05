@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto p-6 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">商品一覧</h1>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">新規作成</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="overflow-x-auto">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th><th>画像</th><th>商品名</th><th>価格</th><th>状態</th><th>操作</th>
        </tr>
      </thead>
      <tbody>
      @forelse($products as $p)
        <tr>
          <td>{{ $p->id }}</td>
          <td>
            @if($p->image_url)
              <img src="{{ $p->image_url }}" alt="" class="w-16 h-12 object-cover rounded">
            @else
              <div class="w-16 h-12 bg-base-200 grid place-items-center rounded text-xs">なし</div>
            @endif
          </td>
          <td class="font-medium">{{ $p->name }}</td>
          <td>{{ number_format($p->price) }}円</td>
          <td>
            <span class="badge {{ $p->is_active ? 'badge-success' : '' }}">
              {{ $p->is_active ? '販売中' : '非公開' }}
            </span>
          </td>
          <td class="flex gap-2">
            <a href="{{ route('admin.products.edit',$p) }}" class="btn btn-sm">編集</a>
            <form method="POST" action="{{ route('admin.products.destroy',$p) }}" onsubmit="return confirm('削除しますか？')">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-error">削除</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center opacity-70">商品がありません</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>

  {{ $products->links() }}
</div>
@endsection
