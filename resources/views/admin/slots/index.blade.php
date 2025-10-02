{{-- resources/views/admin/slots/index.blade.php --}}
@extends('layouts.daisy')
@section('title','スロット管理')
@section('content')
<div class="max-w-5xl mx-auto space-y-4">
  <h1 class="text-xl font-bold">スロット管理（枠数）</h1>

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
                  <th>枠数</th> {{-- 収容数 → 枠数 --}}
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
                </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endforeach

    <div class="flex items-center gap-3">
      <button class="btn btn-primary">一括更新</button>
    </div>
  </form>
</div>
@endsection
