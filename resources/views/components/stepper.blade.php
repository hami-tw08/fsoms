@props([
  'steps' => [],
  'current' => 1,
  'verticalOnSm' => true,
  'showLabels' => true,
])

@php
  $cur = (int) $current;
  $wrapper = $verticalOnSm ? 'steps steps-vertical lg:steps-horizontal' : 'steps steps-horizontal';
@endphp

<ol {{ $attributes->merge(['class' => $wrapper]) }} data-current="{{ $cur }}">  {{-- ★ 追加 --}}
  @foreach($steps as $i => $label)
    @php $index = $i + 1; @endphp
    <li @class([
          'step',
          'step-secondary' => $index === $cur, // 現在
          'step-primary'   => $index <  $cur,  // 完了
        ])>
      @if($showLabels)
        <span class="mt-1 block text-[11px] md:text-sm leading-tight max-w-[9rem] mx-auto">{{ $label }}</span>
      @endif
    </li>
  @endforeach
</ol>
