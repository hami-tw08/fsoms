@extends('layouts.admin')
@section('content')
<div class="max-w-4xl mx-auto p-6 space-y-6">
  <h1 class="text-2xl font-bold">商品を作成</h1>
  <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
    @include('admin.products._form', ['product' => $product])
  </form>
</div>
@endsection
