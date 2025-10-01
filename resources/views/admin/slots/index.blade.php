{{-- resources/views/admin/slots/index.blade.php --}}
@extends('layouts.daisy')
@section('title','スロット管理')
@section('content')
<div class="max-w-5xl mx-auto space-y-4">
  <h1 class="text-xl font-bold">スロット管理（通知閾値／収容数）</h1>

  <form method="GET" class="flex gap-2 items-end">
    <div>
      <label class="label"><span class="label-text">対象日</span></label>
      <input type="date" name="date" value="{{ $date }}" class="input input-bordered" />
    </div>
    <button class="btn btn-primary">表示</button>
  </form>

  @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif

  <form method="POST" action="{{ route('admin.slots.bulk-update') }}" class="space-y-6">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">

    @foreach(['store'=>'店頭','delivery'=>'配送'] as $type => $label)
      <div class="card bg-base-100 shadow">
        <div class="card-body">
          <h2 class="card-title">{{ $label }}（{{ $type }}）</h2>
          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th>時間</th>
                  <th>残数</th>
                  <th>収容数</th>
                  <th>通知閾値</th>
                  <th>通知状態</th>
                </tr>
              </thead>
              <tbody>
              @foreach(($slots[$type] ?? collect()) as $s)
                <tr>
                  <td>{{ \Illuminate\Support\Str::of($s->start_time)->limit(5,'') }}-{{ \Illuminate\Support\Str::of($s->end_time)->limit(5,'') }}</td>
                  <td>{{ $s->remaining }}</td>
                  <td>
                    <input type="number" min="0" max="99" name="items[{{ $s->id }}][capacity]"
                           value="{{ $s->capacity }}" class="input input-bordered w-24" />
                    <input type="hidden" name="items[{{ $s->id }}][id]" value="{{ $s->id }}">
                  </td>
                  <td>
                    <input type="number" min="0" max="99" name="items[{{ $s->id }}][notify_threshold]"
                           value="{{ $s->notify_threshold }}" class="input input-bordered w-24" />
                  </td>
                  <td>
                    @if($s->notified_low_at)
                      <span class="badge badge-warning">通知済 {{ $s->notified_low_at->format('m/d H:i') }}</span>
                    @else
                      <span class="badge">未通知</span>
                    @endif
                  </td>
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endforeach

    <div class="flex items-center gap-3">
      <label class="label cursor-pointer">
        <span class="label-text mr-2">通知状態をリセット</span>
        <input type="checkbox" name="reset_notified" value="1" class="checkbox">
      </label>
      <button class="btn btn-primary">一括更新</button>
    </div>
  </form>
</div>
@endsection
