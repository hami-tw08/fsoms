@php
  $__reservationSteps = [
    '予約日時等を指定する',
    '商品を選択する',
    '注文者情報等を入力する',
    '登録する情報を確認する',
    'ご予約完了',
  ];
@endphp

<x-stepper :steps="$__reservationSteps" :current="$current" class="mb-6" />
