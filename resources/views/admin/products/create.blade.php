@extends('layouts.app')

@section('title', '商品を登録')

@section('content')
  <h1 class="text-2xl font-bold mb-4">商品を登録</h1>

  <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="card bg-base-100 p-6 shadow">
    @include('admin.products._form')
  </form>
@endsection
