@extends('layouts.admin')
@section('title','予約枠')
@section('content')
<div class="flex items-center justify-between mb-4">
  <div class="join">
    <a class="btn join-item {{ request('type')===null?'btn-active':'' }}" href="{{ route('admin.slots.index') }}">すべて</a>
    <a class="btn join-item {{ request('type')==='store'?'btn-active':'' }}" href="{{ route('admin.slots.index',['type'=>'store']) }}">店</a>
    <a class="btn join-item {{ request('type')==='delivery'?'btn-active':'' }}" href="{{ route('admin.slots.index',['type'=>'delivery']) }}">配</a>
  </div>
  <form method="GET" class="join">
    <input class="input input-bordered join-item" type="date" name="date" value="{{ request('date') }}">
    @foreach (['type'] as $keep) @if(request($keep)) <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}"> @endif @endforeach
    <button class="btn btn-primary join-item">絞り込み</button>
  </form>
</div>

<div class="overflow-x-auto">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>日付</th>
        <th>時間</th>
        <th>種別</th>
        <th>定員</th>
        <th>有効</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    @foreach ($slots as $s)
      <tr>
        <td>{{ $s->id }}</td>
        <td>{{ $s->slot_date }}</td>
        <td>{{ \Illuminate\Support\Str::of($s->start_time)->limit(5, '') }} - {{ \Illuminate\Support\Str::of($s->end_time)->limit(5, '') }}</td>
        <td><span class="badge">{{ $s->slot_type }}</span></td>
        <td>{{ $s->capacity }}</td>
        <td>
          @if ($s->is_active)
            <span class="badge badge-success">ON</span>
          @else
            <span class="badge">OFF</span>
          @endif
        </td>
        <td>
          <form method="POST" action="{{ route('admin.slots.toggle', $s->id) }}">
            @csrf
            <button class="btn btn-sm">{{ $s->is_active ? '無効化' : '有効化' }}</button>
          </form>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>

<div class="mt-4">{{ $slots->links() }}</div>
@endsection