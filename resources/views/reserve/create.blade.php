@extends('layouts.daisy')

@section('title','浜通りフラワーマーケット')

@section('content')
  {{-- daisyUI の .btn が overflow:hidden なのでカレンダー日セルは可視化 --}}
  <style>.daycell{ overflow:visible!important; }</style>

  @php
    // Controller から $minDate（Carbon）が来る想定。来ない場合は今日+3日で算出。
    /** @var \Carbon\Carbon|string|null $minDate */
    $___minDate = isset($minDate) ? \Carbon\Carbon::parse($minDate) : \Carbon\Carbon::today()->addDays(3);
    $___minDateStr = $___minDate->toDateString();
    $___minDateLabel = method_exists($___minDate,'isoFormat')
      ? $___minDate->isoFormat('M月D日(ddd)')
      : $___minDate->format('n月j日');
  @endphp

  {{-- ヒーロー：キャッチ＋トップイメージ（md+はオーバーレイ、smは下に配置） --}}
  <div class="mb-8">
    {{-- スマホ：画像だけ全幅表示 --}}
    <div class="md:hidden rounded-2xl overflow-hidden">
      <img src="{{ asset('img/top-image3.png') }}" alt="トップイメージ"
           class="w-full h-auto object-contain">
    </div>

    {{-- md+：高さは画像に任せて、画像は全体表示（トリミングしない） --}}
    <div class="hidden md:block relative rounded-2xl overflow-hidden bg-[#F7D56A]">
      <img src="{{ asset('img/top-image3.png') }}" alt="トップイメージ"
           class="w-full h-auto object-contain">
      <div class="absolute left-6 bottom-6">
        <div class="bg-base-100/90 backdrop-blur shadow-xl rounded-box px-6 py-4 max-w-[52rem]">
          <div class="text-lg font-bold mb-1">当店について</div>
          <div class="text-sm leading-relaxed">
            2027年に浪江でオープンした、1人で切り盛りする小さい花屋です。<br>
            お花をご覧になるのみでも大歓迎！お気軽にお越しください。<br>
            お求めの予定が決まっている場合は、<span class="font-bold">オンライン予約</span>をオススメします。<br>
            なお、配送中はお店を閉めますので、ご来店の際は下記開店時間をご参照願います。
          </div>
        </div>
      </div>
    </div>


  {{-- スマホ：画像の下にメッセージ --}}
  <div class="md:hidden mt-3">
    <div class="bg-base-100 shadow-xl rounded-box px-4 py-3 space-y-3">
      <div>
        <div class="text-base font-bold mb-1">当店について</div>
        <div class="text-sm leading-relaxed">
          2027年に浪江でオープンした、1人で切り盛りする小さい花屋です。<br>
          お花をご覧になるのみでも大歓迎！お気軽にお越しください。<br>
          お求めの予定が決まっている場合は、<span class="font-bold">オンライン予約</span>をオススメします。<br>
          なお、配送中はお店を閉めますので、ご来店の際は下記開店時間をご参照願います。
        </div>
      </div>

      <hr class="opacity-20">
    </div>
  </div>


    {{-- スマホ：画像の下にメッセージ＋添付テキストを表示（崩れ防止） --}}
    <div class="md:hidden mt-3">
      <div class="bg-base-100 shadow-xl rounded-box px-4 py-3 space-y-3">
        <div>
          <div class="text-base font-bold mb-1">当店について</div>
          <div class="text-sm leading-relaxed">
            2027年に浪江でオープンした、1人で切り盛りする小さい花屋です。<br>
            お花をご覧になるのみでも大歓迎！お気軽にお越しください。<br>
            お求めの予定が決まっている場合は、<span class="font-bold">オンライン予約</span>をオススメします。<br>
            なお、配送中はお店を閉めますので、ご来店の際は下記開店時間をご参照願います。
          </div>
        </div>

        <hr class="opacity-20">

        <!-- {{-- 添付テキスト：スマホでもPCでも常に表示（必要に応じて編集OK） --}} -->
        <!-- <div class="text-sm leading-relaxed whitespace-pre-line"> -->
          <!-- ご予約について -->
          <!-- 店頭受取、または配送のご予約を承ります（3日前まで） -->
        <!-- </div> -->
      </div>
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success mb-4">{{ session('status') }}</div>
  @endif

  {{-- 見出し：オンライン予約 --}}
  <div class="flex items-center gap-3 mb-3">
    <h2 class="text-lg md:text-xl font-bold">オンライン予約</h2>
    <div class="badge badge-info badge-outline text-[11px] md:text-xs">
      予約は {{ $___minDateLabel }} 以降が選べます
    </div>
  </div>

{{-- ▼ ステッパー（現在=1） --}}
@php
  $steps = [
    1 => ['label' => '予約日時等を指定する', 'route' => route('reserve.create')],
    2 => ['label' => '商品を選択する',       'route' => route('products.index')],
    3 => ['label' => '注文者情報等を入力する','route' => route('checkout.shipping')],
    4 => ['label' => '登録する情報を確認する','route' => route('checkout.confirm')],  // ルート名はプロジェクトに合わせて
    5 => ['label' => 'ご予約完了',           'route' => route('checkout.complete', [], false) ?? '#' ], // 無ければ'#'
  ];
  $current = 1;
@endphp
<div class="overflow-x-auto mb-4">
  <ul class="steps w-full justify-center mx-auto max-w-4xl min-w-max gap-3" data-stepper>
    @foreach($steps as $i => $s)
      <li class="step {{ $i <= $current ? 'step-primary' : '' }}"
          @if($i < $current) data-href="{{ $s['route'] }}" @endif>
        @if($i < $current)
          <a href="{{ $s['route'] }}" class="block -mx-2 px-2 py-1 group">
            <span class="text-xs sm:text-sm">{{ $s['label'] }}</span>
          </a>
        @else
          <span class="block -mx-2 px-2 py-1 {{ $i === $current ? 'font-semibold' : '' }}">
            <span class="text-xs sm:text-sm">{{ $s['label'] }}</span>
          </span>
        @endif
      </li>
    @endforeach
  </ul>
</div>



  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" id="calendarRoot" data-min-date="{{ $___minDateStr }}">
    {{-- 左：カレンダー（2カラム） --}}
    <div class="lg:col-span-2">
      <div class="card bg-base-100 shadow-xl overflow-x-auto">
        <div class="card-body min-w-full">
          <h2 class="text-lg md:text-xl font-bold">オンライン予約用カレンダー：予約日の指定</h2>
          <div class="mt-3 flex flex-wrap gap-2 items-center">
            <div class="badge badge-outline">カレンダーをクリックして日付を選択</div>
            <div class="text-sm opacity-70">○：枠あり / ×：枠なし</div>
            <div class="text-sm opacity-70">※ {{ $___minDateLabel }} より前は選べません</div>
          </div>
          <div class="flex items-center justify-between">
            <a href="{{ route('reserve.create',['month'=>$prevMonth]) }}" class="btn btn-ghost">« Prev</a>
            <h3 class="card-title text-2xl">{{ $firstDay->format('Y年 n月') }}</h3>
            <a href="{{ route('reserve.create',['month'=>$nextMonth]) }}" class="btn btn-ghost">Next »</a>
          </div>

          <div class="mt-4">
            <div class="grid grid-cols-7 text-center font-semibold text-[11px] sm:text-sm opacity-70">
              <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
            </div>

            <div class="mt-2 space-y-2">
              @foreach($weeks as $week)
                <div class="grid grid-cols-7 gap-1 sm:gap-2">
                  @foreach($week as $d)
                    @php
                      $rs = (int)($d['remain_store'] ?? 0);
                      $rd = (int)($d['remain_deliv'] ?? 0);
                      $hasStore   = $rs > 0;
                      $hasDeliv   = $rd > 0;
                      $beforeMin  = isset($d['before_min'])
                                      ? (bool)$d['before_min']
                                      : (\Carbon\Carbon::parse($d['date'])->lt($___minDate));
                      $inMonth    = ($d['in_month'] ?? true);
                      $clickable  = $inMonth && !$beforeMin && ($hasStore || $hasDeliv);
                      $disabledReason = !$inMonth ? '当月外'
                                        : ($beforeMin ? '3日前ルールにより選択不可'
                                        : (!($hasStore || $hasDeliv) ? '空き枠なし' : ''));
                    @endphp

                    <button type="button"
                      @if($clickable)
                        data-date="{{ $d['date'] }}"
                      @else
                        disabled
                        title="{{ $disabledReason }}"
                        aria-disabled="true"
                      @endif
                      aria-label="{{ $d['date'] }} の空き状況：店{{ $hasStore ? 'あり' : 'なし' }} 配{{ $hasDeliv ? 'あり' : 'なし' }}"
                      class="daycell btn btn-ghost p-2 h-20 md:h-20 w-full text-left rounded-xl
                             {{ !$inMonth ? 'opacity-40' : '' }}
                             {{ $beforeMin ? 'cursor-not-allowed opacity-40' : '' }}
                             {{ $clickable ? '' : 'cursor-not-allowed opacity-40' }}
                             {{ $d['is_today'] ? 'border border-primary' : '' }}"
                    >
                      {{-- 日付 --}}
                      <div class="w-full flex items-start justify-between">
                        <span class="text-xs md:text-sm font-medium">{{ $d['day'] }}</span>
                        @if($beforeMin)
                          <span class="badge badge-xs md:badge-sm badge-ghost">-3D</span>
                        @endif
                      </div>

                      {{-- 店／配：smは縦並び、md+は横並び --}}
                      <div class="mt-1 w-full flex flex-col md:flex-row items-start md:items-center gap-0.5 md:gap-2">
                        {{-- 店 --}}
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5
                                     text-[10px] md:text-xs font-medium ring-1 whitespace-nowrap
                                     {{ $hasStore ? 'bg-amber-300 text-amber-900 ring-amber-400' : 'bg-base-200 text-base-content/50 ring-base-300' }}">
                          <span class="leading-none">店</span><span class="leading-none">{{ $hasStore ? '○' : '×' }}</span>
                        </span>
                        {{-- 配 --}}
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5
                                     text-[10px] md:text-xs font-medium ring-1 whitespace-nowrap
                                     {{ $hasDeliv ? 'bg-sky-300 text-sky-900 ring-sky-400' : 'bg-base-200 text-base-content/50 ring-base-300' }}">
                          <span class="leading-none">配</span><span class="leading-none">{{ $hasDeliv ? '○' : '×' }}</span>
                        </span>
                      </div>

                      <span class="text-[10px] md:text-[11px] opacity-70 selected-mark hidden mt-auto">選択中</span>
                    </button>
                  @endforeach
                </div>
              @endforeach
            </div>
          </div>


        </div>
      </div>
    </div>

    {{-- 右：最小ステップの予約フォーム（1カラム） --}}
    <div>
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h3 class="card-title">予約フォーム</h3>

          <div class="text-sm text-gray-600 space-y-1 mb-2">
            <div>ご予約のながれ：①カレンダーで日付を選択 → ②受取り方法を選択 → ③受取り希望時間を選択 → ④商品選択へ</div>
            <div>店：店頭受取り　配：配送</div>
            <div>配送可能エリア：南相馬市小高区、浪江町、双葉町、大熊町</div>
            <div id="ruleHelper" class="opacity-80"></div>
          </div>

          {{-- 表示用のクイックフォーム（送信しない） --}}
          <form id="quickReserveForm" onsubmit="return false;" class="space-y-4">
            @csrf
            <div class="form-control">
              <label class="label"><span class="label-text">受取り日</span></label>
              <input type="text" id="selected_date_label" class="input input-bordered" placeholder="カレンダーから日付を選択" disabled>
              <input type="hidden" id="selected_date">
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">受取り方法</span></label>
              <select id="method" class="select select-bordered" disabled>
                <option value="" selected>選択してください</option>
                <option value="store">店頭受取</option>
                <option value="delivery">配送　《次の画面で4,000円以上の商品を選択ください》</option>
              </select>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">希望受取り時間</span></label>
              <select id="time" class="select select-bordered" disabled>
                <option value="" selected>先に受取り方法を選択</option>
              </select>
            </div>
          </form>

          {{-- ★ セッション保存用の実フォーム（ReservationController@storeCreateStep へPOST） --}}
          <form id="reserveMetaForm" method="POST" action="{{ route('reserve.storeCreateStep') }}" class="hidden">
            @csrf
            <input type="hidden" name="receive_method" id="receive_method">
            <input type="hidden" name="receive_date" id="receive_date">
            <input type="hidden" name="receive_time" id="receive_time">
            {{-- 将来の予約一覧集計用に、開始/終了も同送しておく（今はController側で未使用でもOK） --}}
            <input type="hidden" name="receive_time_start" id="receive_time_start">
            <input type="hidden" name="receive_time_end" id="receive_time_end">
          </form>

          {{-- ★ 新設：3項目そろったら有効になる Next 「リンク」ボタン（置き換え①） --}}
          <div class="card-actions mt-2">
            <a
              id="nextLink"
              href="{{ route('products.index') }}"
              class="btn btn-primary w-full pointer-events-none opacity-50"
              aria-disabled="true"
              data-post-action="{{ route('reserve.storeCreateStep') }}"
            >
              次へ（商品選択へ進む）
            </a>
          </div>

          <!-- <div class="mt-3 text-xs text-gray-500">
            配達エリア：浪江 / 双葉 / 大熊 / 小高区
          </div> -->
        </div>
      </div>
    </div>
  </div>

  {{-- ========= ここからフル幅（PCは横幅いっぱい）セクション ========= --}}
  <div class="mt-8 space-y-4">
    {{-- ▼ 配送について（フル幅） --}}
    <div class="card bg-base-100 shadow-md">
      <div class="card-body py-4">
        <h3 class="text-base md:text-lg font-bold">配送について</h3>
        <p class="text-sm md:text-base mt-1">
          福島県浪江町／南相馬市小高区／双葉町／大熊町 への配送を承ります
        </p>
        <div class="text-sm md:text-base mt-2 space-y-1">
          <p>＊配送は4,000円（税込）以上のお買い上げでご利用いただけます</p>
          <p>＊２日以内の配送はお電話にてご相談ください。お急ぎ配送料1,800円頂戴します</p>
        </div>
      </div>
    </div>

    {{-- ▼ 開店時間（フル幅／レスポンシブ表示） --}}
    <div class="card bg-base-100 shadow-md">
      <div class="card-body py-4">
        <h3 class="text-base md:text-lg font-bold">開店時間</h3>

        {{-- md以上：表（曜日×時間枠） --}}
        <div class="hidden md:block mt-3">
          <div class="overflow-x-auto">
            <table class="table w-full">
              <thead>
                <tr>
                  <th class="w-28">曜日</th>
                  <th>10:00-11:00</th>
                  <th>11:00-12:00</th>
                  <th>12:00-14:00</th>
                  <th>14:00-16:00</th>
                  <th>16:00-17:00</th>
                  <th>17:00-18:30</th>
                  <th>18:30-19:30</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <th>水、金</th>
                  <td>クローズ(仕入れ)</td>
                  <td>クローズ(仕入れ)</td>
                  <td>クローズ(配達)</td>
                  <td class="text-red-600 font-semibold">オープン</td>
                  <td>クローズ(配達)</td>
                  <td class="text-red-600 font-semibold">オープン</td>
                  <td>クローズ(配達)</td>
                </tr>
                <tr>
                  <th>木、土、日</th>
                  <td>クローズ(配達)</td>
                  <td class="text-red-600 font-semibold">オープン</td>
                  <td>クローズ(配達)</td>
                  <td class="text-red-600 font-semibold">オープン</td>
                  <td>クローズ(配達)</td>
                  <td class="text-red-600 font-semibold">オープン</td>
                  <td>クローズ(配達)</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        {{-- スマホ：2枚のカード（水・金 / 木・土・日） --}}
        <div class="md:hidden mt-2 grid gap-3">
          @php
            $slots = ['10:00-11:00','11:00-12:00','12:00-14:00','14:00-16:00','16:00-17:00','17:00-18:30','18:30-19:30'];
            $wk    = ['クローズ(仕入れ)','クローズ(仕入れ)','クローズ(配達)','オープン','クローズ(配達)','オープン','クローズ(配達)']; // 水・金
            $wkend = ['クローズ(配達)','オープン','クローズ(配達)','オープン','クローズ(配達)','オープン','クローズ(配達)'];       // 木・土・日
          @endphp

          {{-- 水・金 --}}
          <div class="rounded-box border border-base-300 bg-base-100 p-3">
            <div class="font-semibold mb-1">水、金</div>
            @foreach($slots as $i => $label)
              @php $val = $wk[$i]; @endphp
              <div class="text-sm py-1 flex items-center justify-between border-b border-base-200 last:border-0">
                <span class="opacity-70">{{ $label }}</span>
                <span class="{{ $val === 'オープン' ? 'text-red-600 font-semibold' : '' }}">{{ $val }}</span>
              </div>
            @endforeach
          </div>

          {{-- 木・土・日 --}}
          <div class="rounded-box border border-base-300 bg-base-100 p-3">
            <div class="font-semibold mb-1">木、土、日</div>
            @foreach($slots as $i => $label)
              @php $val = $wkend[$i]; @endphp
              <div class="text-sm py-1 flex items中心 justify-between border-b border-base-200 last:border-0">
                <span class="opacity-70">{{ $label }}</span>
                <span class="{{ $val === 'オープン' ? 'text-red-600 font-semibold' : '' }}">{{ $val }}</span>
              </div>
            @endforeach
          </div>
        </div>

        {{-- 備考 --}}
        <p class="text-xs md:text-sm mt-3 opacity-80">
          ＊月・火は定休、1/1～1/3はお休み。その他のお休みはSNSでお知らせします。
        </p>
      </div>
    </div>

    {{-- ▼ アクセス・お問い合わせ（フル幅） --}}
    <div class="card bg-base-100 shadow-md">
      <div class="card-body py-4">
        <h3 class="text-base md:text-lg font-bold">アクセス・お問い合わせ</h3>
        <div class="mt-2 text-sm md:text-base leading-relaxed space-y-1">
          <p>福島県浪江町権現堂○○○○</p>
          <p>JR浪江駅から徒歩○分</p>
          <p>駐車場○台</p>
        </div>
        <div class="mt-3 text-sm md:text-base leading-relaxed space-y-1">
          <p>電話：<a href="tel:080-xxxx-xxxx" class="link">080-xxxx-xxxx</a></p>
          <p>メール：<a href="mailto:test@example.com" class="link">test@example.com</a></p>
        </div>
      </div>
    </div>
  </div>
  {{-- ========= フル幅セクション終わり ========= --}}

  {{-- JS：日付→方法→時間（法則でフィルタ）→【3項目揃ったらNextが有効→POSTでセッション保存→リンク遷移】 --}}
<script>
  // ========= 参照 =========
  const calendarRoot = document.getElementById('calendarRoot');
  const MIN_DATE = calendarRoot?.dataset?.minDate || '';

  const dateHidden = document.getElementById('selected_date');
  const dateLabel  = document.getElementById('selected_date_label');
  const methodSel  = document.getElementById('method');
  const timeSel    = document.getElementById('time');
  const ruleHelper = document.getElementById('ruleHelper');
  const nextLink   = document.getElementById('nextLink');

  // POST用 hidden
  const metaForm   = document.getElementById('reserveMetaForm');
  const fMethod    = document.getElementById('receive_method');
  const fDate      = document.getElementById('receive_date');
  const fTime      = document.getElementById('receive_time');
  const fTimeStart = document.getElementById('receive_time_start');
  const fTimeEnd   = document.getElementById('receive_time_end');

  // ========= ユーティリティ =========
  function isoWeekday(dateStr){ const d = new Date(dateStr + 'T00:00:00'); const js = d.getDay(); return js === 0 ? 7 : js; }
  function ruleKeyByDow(dow){ if (dow===3||dow===5) return 'WED_FRI'; if (dow===4||dow===6||dow===7) return 'THU_SAT_SUN'; return null; }
  function toHHMM(s){ return (s||'').slice(0,5); }
  function formatJPDate(iso){ if(!iso) return ''; const [y,m,d]=iso.split('-').map(n=>parseInt(n,10)); return `${y}年${m}月${d}日`; }
  function lt(a,b){ return a && b && a < b; }

  const RULES = {
    delivery: {
      WED_FRI:   ["12:00-14:00","16:00-17:00","18:30-19:30"],
      THU_SAT_SUN:["10:00-11:00","12:00-14:00","16:00-17:00","18:30-19:30"]
    },
    store: {
      WED_FRI:   ["14:00-16:00","17:00-18:30"],
      THU_SAT_SUN:["11:00-12:00","14:00-16:00","17:00-18:30"]
    }
  };
  const RULE_CAPACITY = {
    delivery: (range) => range === "12:00-14:00" ? 3 : 1,
    store:    () => 3
  };
  function listAllowedRanges(dateStr, method){
    const key = ruleKeyByDow(isoWeekday(dateStr));
    if (!key) return [];
    return (RULES[method]?.[key] ?? []).slice();
  }

  function readyToProceed(){
    const d = !!dateHidden.value;
    const m = !!methodSel.value;
    const t = !!timeSel.value && !timeSel.selectedOptions[0]?.disabled;
    return d && m && t;
  }
  function updateNextButtonState(){
    const ok = readyToProceed();
    nextLink.classList.toggle('pointer-events-none', !ok);
    nextLink.classList.toggle('opacity-50', !ok);
    nextLink.setAttribute('aria-disabled', String(!ok));
  }
  function resetMethodAndTime() {
    methodSel.value = '';
    methodSel.disabled = true;
    timeSel.innerHTML = '<option value="" selected>先に受取り方法を選択</option>';
    timeSel.disabled = true;
    ruleHelper.textContent = '';
    updateNextButtonState();
  }
  function enableMethod() {
    methodSel.disabled = false;
    timeSel.disabled = true;
    timeSel.innerHTML = '<option value="" selected>先に受取り方法を選択</option>';
    updateRuleHint();
    updateNextButtonState();
  }
  function updateRuleHint(){
    const d = dateHidden.value, m = methodSel.value;
    if (!d || !m) { ruleHelper.textContent = ''; return; }
    const key = ruleKeyByDow(isoWeekday(d));
    const ranges = listAllowedRanges(d, m);
    const label = m === 'delivery' ? '配達' : '店頭受取';
    const wk = {WED_FRI:'水・金', THU_SAT_SUN:'木・土・日'}[key] || '休業日';
    if (ranges.length) ruleHelper.innerHTML = `選択日（${wk}）の<strong>${label}</strong>受付時間：${ranges.join(' / ')}`;
    else ruleHelper.textContent = 'この曜日は受付していません';
  }

  // ========= ★ カレンダー：イベント委譲 =========
  let selectedBtn = null;
  calendarRoot?.addEventListener('click', (e) => {
    const btn = e.target.closest('.daycell[data-date]');
    if (!btn || !calendarRoot.contains(btn)) return;

    // 無効ボタンは弾く（disabled属性やaria-disabledで判定）
    if (btn.hasAttribute('disabled') || btn.getAttribute('aria-disabled') === 'true') return;

    const date = btn.getAttribute('data-date');
    if (!date) return;

    if (MIN_DATE && lt(date, MIN_DATE)) {
      alert(`この日は選べません。${formatJPDate(MIN_DATE)} 以降を選択してください。`);
      return;
    }

    // 値のセット
    dateHidden.value = date;
    dateLabel.value  = formatJPDate(date);

    // 見た目
    if (selectedBtn) {
      selectedBtn.classList.remove('ring-2','ring-slate-400','ring-offset-1');
      selectedBtn.querySelector('.selected-mark')?.classList.add('hidden');
    }
    selectedBtn = btn;
    btn.classList.add('ring-2','ring-slate-400','ring-offset-1');
    btn.querySelector('.selected-mark')?.classList.remove('hidden');

    enableMethod();
    updateNextButtonState();
  });

  // ========= 受取り方法 → 時間帯 =========
  methodSel.addEventListener('change', async () => {
    const d = dateHidden.value, m = methodSel.value;
    updateRuleHint();
    timeSel.innerHTML = '';
    if (!d || !m) {
      timeSel.disabled = true;
      timeSel.innerHTML = '<option value="" selected>日付/方法の選択を確認してください</option>';
      updateNextButtonState();
      return;
    }

    timeSel.disabled = true;
    timeSel.innerHTML = '<option value="" selected>読み込み中...</option>';

    // サーバ枠
    let serverSlots = [];
    try {
      const res = await fetch(`/slots?date=${encodeURIComponent(d)}&slot_type=${encodeURIComponent(m)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      serverSlots = await res.json();
    } catch (e) {
      serverSlots = [];
    }

    const allowed = listAllowedRanges(d, m);
    const normalized = (serverSlots || []).map(s => ({
      start: toHHMM(s.start_time),
      end:   toHHMM(s.end_time),
      remaining: (typeof s.remaining === 'number') ? s.remaining : null
    }));

    const intersection = allowed.map(range => {
      const [st, en] = range.split('-');
      const hit = normalized.find(x => x.start === st && x.end === en) || null;
      return { start: st, end: en, serverRemaining: hit ? hit.remaining : null };
    });

    if (!intersection.length) {
      timeSel.disabled = true;
      timeSel.innerHTML = '<option value="" selected>選択可能な時間がありません</option>';
      updateNextButtonState();
      return;
    }

    timeSel.innerHTML = '';
    timeSel.appendChild(new Option('選択してください','',true,true));

    for (const s of intersection) {
      const range = `${s.start}-${s.end}`;
      const sr = s.serverRemaining;

      // デフォルトは「時間だけ」
      let text = range;
      let disabled = false;

      // サーバーが満席 or 未提供のときのラベルだけは残す（必要なら下の2行ごと消してもOK）
      if (sr === 0)   { text = `${range}（受付を終了しました）`;   disabled = true; }
      if (sr === null){ text = `${range}（準備中）`; disabled = true; }

      const opt = new Option(text, range);
      opt.dataset.start = s.start;
      opt.dataset.end   = s.end;
      opt.disabled      = disabled;
      timeSel.appendChild(opt);
    }


    timeSel.disabled = false;
    updateNextButtonState();
  });

  // ========= 時間選択 → hidden =========
  timeSel.addEventListener('change', () => {
    const d = dateHidden.value, m = methodSel.value, v = timeSel.value;
    if (!d || !m || !v) { updateNextButtonState(); return; }
    const [st,en] = v.split('-');
    fDate.value = d;
    fMethod.value = m;
    fTime.value = st;
    fTimeStart.value = st;
    fTimeEnd.value = en;
    updateNextButtonState();
  });

  // ========= 次へ（保存→遷移） =========
  nextLink.addEventListener('click', async (e) => {
    if (!readyToProceed()) { e.preventDefault(); return; }
    e.preventDefault();

    if (!fDate.value || !fMethod.value || !fTime.value) {
      const v = timeSel.value || '';
      if (v) {
        const [st, en] = v.split('-');
        fDate.value = dateHidden.value;
        fMethod.value = methodSel.value;
        fTime.value = st;
        fTimeStart.value = st;
        fTimeEnd.value = en;
      }
    }

    const token = document.querySelector('#reserveMetaForm input[name="_token"]')?.value || '';
    try {
      await fetch(nextLink.dataset.postAction, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
        body: new FormData(metaForm),
        redirect: 'follow',
      });
    } catch (_) { return; }

    window.location.href = nextLink.href;
  });

  // ========= 初期化 =========
  (function init(){
    methodSel.disabled = true;
    timeSel.disabled = true;
    updateNextButtonState();
  })();
</script>

@endsection
