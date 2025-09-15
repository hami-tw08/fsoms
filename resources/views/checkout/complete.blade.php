@extends('layouts.daisy')

@section('title','完了')

@section('content')
<div class="container mx-auto p-4">
  <div class="card bg-base-100 shadow p-8 text-center">
    @if(session('ok'))
      <div class="alert alert-success mb-4">{{ session('ok') }}</div>
    @endif
    <h1 class="text-2xl font-bold mb-2">ご予約ありがとうございます</h1>
    <p class="text-sm text-gray-600">
      スタッフ確認後、必要に応じてご連絡いたします。<br>
      受取方法：店頭受取/配送、受取日時は最終確認画面の内容どおりです。
    </p>
    <div class="mt-6">
      <a href="{{ route('products.index') }}" class="btn btn-primary">商品を見る</a>
    </div>
  </div>
</div>
@endsection
