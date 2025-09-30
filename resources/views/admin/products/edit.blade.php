@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto p-6 space-y-6">
  <h1 class="text-2xl font-bold">商品を編集</h1>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    @include('admin.products._form', ['product' => $product])
  </form>
</div>
@endsection
