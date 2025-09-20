@extends('layouts.admin')
@section('title','予約一覧')
@section('content')
<div class="flex items-center justify-between mb-4">
  <form method="GET" class="join">
    <input class="input input-bordered join-item" type="text" name="q" value="{{ request('q') }}" placeholder="氏名/電話/備考…">
    <button class="btn btn-primary join-item">検索</button>
  </form>
</div>

<div class="overflow-x-auto">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>枠ID</th>
        <th>氏名</th>
        <th>電話</th>
        <th>商品ID</th>
        <th>受取/配達</th>
        <th>作成</th>
      </tr>
    </thead>
    <tbody>
    @foreach ($reservations as $r)
      <tr>
        <td>{{ $r->id }}</td>
        <td>{{ $r->slot_id ?? '-' }}</td>
        <td>{{ $r->guest_name ?? '-' }}</td>
        <td>{{ $r->guest_phone ?? '-' }}</td>
        <td>{{ $r->product_id ?? '-' }}</td>
        <td>{{ $r->method ?? $r->delivery_area ?? '-' }}</td>
        <td>{{ $r->created_at }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>

<div class="mt-4">{{ $reservations->links() }}</div>
@endsection