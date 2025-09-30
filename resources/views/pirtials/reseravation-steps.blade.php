@php
  $__reservationSteps = [
    '予約日時・受取り方法',
    '商品の選択',
    '予約者情報の入力',
    '入力情報の確認',
    '予約完了',
  ];
@endphp

<x-stepper :steps="$__reservationSteps" :current="$current" class="mb-6" />
