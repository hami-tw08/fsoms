@props([
  // ['予約日時…','商品を選択…',...] の配列 or 1始まりの連想配列
  'steps' => [],
  // 現在位置（1始まり）
  'current' => 1,
  // sm以下で縦並びにするか
  'verticalOnSm' => false,
  // ラベル表示するか
  'showLabels' => true,
  // 「1へ戻る」を禁止（= true）※ご要望どおり
  'banBackToOne' => true,
  // 追加クラス
  'class' => '',
])

@php
  // steps を 1始まりの配列に正規化
  $labels = [];
  $i = 1;
  foreach ($steps as $k => $v) {
    $labels[$i] = is_array($v) ? ($v['label'] ?? (string)$v) : (string)$v;
    $i++;
  }

  // 水平/垂直クラス決定（daisyUI steps）
  $orientation = $verticalOnSm ? 'steps-vertical sm:steps-horizontal' : 'steps-horizontal';

  // クラス合成
  $ulClass = trim("steps {$orientation} {$class}");
@endphp

<div class="w-full">
  <div class="overflow-x-auto">
    <ul class="{{ $ulClass }}">
      @foreach ($labels as $idx => $label)
        @php
          $isDone = $idx <= $current;
          $liClass = $isDone ? 'step step-primary' : 'step';
        @endphp

        <li class="{{ $liClass }} relative shrink-0">
          {{-- ラベル --}}
          @if($showLabels)
            <span class="mt-1 block text-[11px] md:text-sm leading-tight max-w-[9rem] mx-auto pointer-events-none relative z-[1] {{ $idx === $current ? 'font-semibold' : '' }}">
              {{ $label }}
            </span>
          @endif

          {{-- ※このコンポーネントは「リンクは張らない」。 
               どこに飛ぶかは“呼び出し側”が <a> で覆う想定にすると役割分離でき、
               foreach の崩れや入れ子の事故も防げます。 --}}
        </li>
      @endforeach
    </ul>
  </div>
</div>
