@extends('layouts.app')

@section('title', '商品を編集')

@section('content')
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">商品を編集</h1>
    <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('削除しますか？');">
      @csrf @method('DELETE')
      <button class="btn btn-error btn-outline">削除</button>
    </form>
  </div>

  <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="card bg-base-100 p-6 shadow">
    @csrf @method('PUT')
    @include('admin.products._form')
  </form>
@endsection
