@extends('layouts.daisy')

@section('title','浜通りフラワーマーケット')

@section('content')
  {{-- daisyUI の .btn が overflow:hidden なのでカレンダー日セルは可視化 --}}
  <style>.daycell{ overflow:visible!important; }</style>

  {{-- ヒーロー：キャッチ＋トップイメージ（md+はオーバーレイ、smは下に配置） --}}
  <div class="mb-8">
    <h1 class="text-xl md:text-2xl font-bold leading-tight mb-3">
      日々の暮らしに添える花<br class="hidden sm:block">大切な方に贈る花
    </h1>

    {{-- 画像：スマホは全体表示、md+ はヒーロー演出 --}}
    <div class="rounded-2xl overflow-hidden">
      {{-- sm（〜md-1）：横幅フィット＆全体表示 --}}
      <img src="{{ asset('img/top-image.png') }}" alt="トップイメージ"
           class="block md:hidden w-full h-auto object-contain">

      {{-- md+：固定高＋cover＋オーバーレイ --}}
      <div class="hidden md:block relative h-80 lg:h-96">
        <img src="{{ asset('img/top-image.png') }}" alt="トップイメージ"
             class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute left-6 bottom-6">
          <div class="bg-base-100/90 backdrop-blur shadow-xl rounded-box px-6 py-4 max-w=[52rem] max-w-[52rem]">
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

        {{-- 添付テキスト：スマホでもPCでも常に表示（必要に応じて編集OK） --}}
        <div class="text-sm leading-relaxed whitespace-pre-line">
          ご予約について
          店頭受取、または配送のご予約を承ります（3日前まで）
        </div>
      </div>
    </div>
  </div>

  @if (session('status'))
    <div class="alert alert-success mb-4">{{ session('status') }}</div>
  @endif

  {{-- 見出し：オンライン予約カレンダー --}}
  <h2 class="text-lg md:text-xl font-bold mb-3">オンライン予約カレンダー</h2>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- 左：カレンダー（2カラム） --}}
    <div class="lg:col-span-2">
      <div class="card bg-base-100 shadow-xl overflow-x-auto">
        <div class="card-body min-w-full">
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
                      $hasStore = $rs > 0;
                      $hasDeliv = $rd > 0;
                      $clickable = ($d['in_month'] ?? true) && ($hasStore || $hasDeliv);
                    @endphp

                    <button type="button"
                      @if($clickable) data-date="{{ $d['date'] }}" @else disabled @endif
                      aria-label="{{ $d['date'] }} の空き状況：店{{ $hasStore ? 'あり' : 'なし' }} 配{{ $hasDeliv ? 'あり' : 'なし' }}"
                      class="daycell btn btn-ghost p-2 h-20 md:h-20 w-full text-left rounded-xl
                             {{ !$d['in_month'] ? 'opacity-40' : '' }}
                             {{ $clickable ? '' : 'cursor-not-allowed opacity-40' }}
                             {{ $d['is_today'] ? 'border border-primary' : '' }}"
                    >
                      {{-- 日付 --}}
                      <div class="w-full flex items-start justify-between">
                        <span class="text-xs md:text-sm font-medium">{{ $d['day'] }}</span>
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

          <div class="mt-3 flex flex-wrap gap-2 items-center">
            <div class="badge badge-outline">カレンダーをクリックして日付を選択</div>
            <div class="text-xs opacity-70">○：枠あり / ×：枠なし</div>
          </div>
        </div>
      </div>
    </div>

    {{-- 右：最小ステップの予約フォーム（1カラム） --}}
    <div>
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h3 class="card-title">予約フォーム</h3>

          <div class="text-xs text-gray-600 space-y-1 mb-2">
            <div>ご予約のながれ：①カレンダーで日付を選択 → ②受取り方法 → ③時間 → ④商品選択へ</div>
            <div>店：店頭受取り　配：配送</div>
            <div>×になっている場合は選択できません</div>
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
                <option value="delivery">配達</option>
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
          </form>

          <div class="mt-3 text-xs text-gray-500">
            配達エリア：浪江 / 双葉 / 大熊 / 小高区
          </div>
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
          福島県　浪江町／南相馬市小高区／双葉町／大熊町 への配送承ります
        </p>
        <div class="text-sm md:text-base mt-2 space-y-1">
          <p>＊配送は4,000円（税込）以上のお買い上げでご利用いただけます</p>
          <p>＊２日以内の配送はお電話にてご相談ください。お急ぎ配送料1,800円頂戴します（浪江含む）</p>
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
              <div class="text-sm py-1 flex items-center justify-between border-b border-base-200 last:border-0">
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

  {{-- JS：日付→方法→時間→【POSTでセッション保存】→Controllerでproducts.indexへリダイレクト --}}
  <script>
    const dayCells  = document.querySelectorAll('.daycell');
    const dateHidden= document.getElementById('selected_date');
    const dateLabel = document.getElementById('selected_date_label');
    const methodSel = document.getElementById('method');
    const timeSel   = document.getElementById('time');

    // セッション保存用フォーム要素
    const metaForm  = document.getElementById('reserveMetaForm');
    const fMethod   = document.getElementById('receive_method');
    const fDate     = document.getElementById('receive_date');
    const fTime     = document.getElementById('receive_time');

    let selectedBtn = null;

    function resetMethodAndTime() {
      methodSel.value = '';
      methodSel.disabled = true;
      timeSel.innerHTML = '<option value="" selected>先に受取り方法を選択</option>';
      timeSel.disabled = true;
    }
    function enableMethod() {
      methodSel.disabled = false;
      timeSel.disabled = true;
      timeSel.innerHTML = '<option value="" selected>先に受取り方法を選択</option>';
    }
    function formatJPDate(iso) {
      if (!iso) return '';
      const [y,m,d] = iso.split('-').map(n=>parseInt(n,10));
      if (!y||!m||!d) return iso;
      return `${y}年${m}月${d}日`;
    }

    dayCells.forEach(btn => {
      btn.addEventListener('click', () => {
        if (!btn.hasAttribute('data-date')) return;
        const date = btn.getAttribute('data-date');
        dateHidden.value = date;
        dateLabel.value  = formatJPDate(date);

        if (selectedBtn) {
          selectedBtn.classList.remove('ring-2','ring-slate-400','ring-offset-1');
          selectedBtn.querySelector('.selected-mark')?.classList.add('hidden');
        }
        selectedBtn = btn;
        btn.classList.add('ring-2','ring-slate-400','ring-offset-1');
        btn.querySelector('.selected-mark')?.classList.remove('hidden');

        // ★ 自動スクロールは削除（下に動かないようにする）
        enableMethod();
      });
    });

    methodSel.addEventListener('change', async () => {
      const d = dateHidden.value, m = methodSel.value;
      timeSel.innerHTML = '';
      if (!d || !m) {
        timeSel.disabled = true;
        timeSel.innerHTML = '<option value="" selected>日付/方法の選択を確認してください</option>';
        return;
      }
      timeSel.disabled = true;
      timeSel.innerHTML = '<option value="" selected>読み込み中...</option>';
      try {
        const res = await fetch(`/slots?date=${encodeURIComponent(d)}&slot_type=${encodeURIComponent(m)}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (!Array.isArray(data) || data.length === 0) {
          timeSel.disabled = true;
          timeSel.innerHTML = '<option value="" selected>選択可能な時間がありません</option>';
          return;
        }
        timeSel.innerHTML = '';
        timeSel.appendChild(new Option('選択してください','',true,true));
        for (const s of data) {
          timeSel.appendChild(new Option(`${s.start_time} - ${s.end_time}（残り${s.remaining}）`, s.start_time));
        }
        timeSel.disabled = false;
      } catch {
        timeSel.disabled = true;
        timeSel.innerHTML = '<option value="" selected>取得エラー。リロードしてください</option>';
      }
    });

    // 時間選択後は Controller(ReservationController@storeCreateStep) にPOSTしてセッションへ保存
    timeSel.addEventListener('change', () => {
      const d = dateHidden.value, m = methodSel.value, t = timeSel.value;
      if (!d || !m || !t) return;
      fDate.value   = d;
      fMethod.value = m;
      fTime.value   = t;
      metaForm.submit();
    });

    resetMethodAndTime();
  </script>
@endsection
