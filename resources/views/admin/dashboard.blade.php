@extends('layouts.admin')
@section('title','Dashboard')
@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
  <div class="card bg-base-100 shadow">
    <div class="card-body">
      <div class="stat">
        <div class="stat-title">本日の予約作成</div>
        <div class="stat-value">{{ $todayReservations }}</div>
        <div class="stat-desc">{{ $today }} に作成</div>
      </div>
    </div>
  </div>
  <div class="card bg-base-100 shadow">
    <div class="card-body">
      <div class="stat">
        <div class="stat-title">総予約数</div>
        <div class="stat-value">{{ $totalReservations }}</div>
      </div>
    </div>
  </div>
  <div class="card bg-base-100 shadow">
    <div class="card-body">
      <div class="stat">
        <div class="stat-title">有効な予約枠</div>
        <div class="stat-value">{{ $activeSlots }}</div>
      </div>
    </div>
  </div>
</div>
@endsection