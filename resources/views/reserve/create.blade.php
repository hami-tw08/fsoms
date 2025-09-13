@extends('layouts.daisy')

@section('title','Make a Reservation')

@section('content')
  @if (session('status'))
    <div class="alert alert-success mb-4">{{ session('status') }}</div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- 左：でかいカレンダー（2カラムぶん） --}}
    <div class="lg:col-span-2">
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <div class="flex items-center justify-between">
            <a href="{{ route('reserve.create',['month'=>$prevMonth]) }}" class="btn btn-ghost">« Prev</a>
            <h2 class="card-title text-2xl">
              {{ $firstDay->format('Y年 n月') }}
            </h2>
            <a href="{{ route('reserve.create',['month'=>$nextMonth]) }}" class="btn btn-ghost">Next »</a>
          </div>

          <div class="mt-4">
            <div class="grid grid-cols-7 text-center font-semibold text-sm opacity-70">
              <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
            </div>
            <div class="mt-2 space-y-2">
              @foreach($weeks as $week)
                <div class="grid grid-cols-7 gap-2">
                  @foreach($week as $d)
                    <button
                      type="button"
                      data-date="{{ $d['date'] }}"
                      class="daycell btn btn-sm h-20 flex flex-col justify-start items-start
                        @if(!$d['in_month']) btn-ghost opacity-40 @else btn-ghost @endif
                        @if($d['is_today']) border border-primary @endif"
                    >
                      <div class="w-full flex items-start justify-between">
                        <span class="text-sm">{{ $d['day'] }}</span>
                      </div>

                      {{-- バッジ：店頭/配達 残り数 --}}
                      <div class="mt-1 space-x-1">
                        @php $rs = (int)$d['remain_store']; $rd = (int)$d['remain_deliv']; @endphp
                        {{-- 店頭（store） --}}
                        <span class="badge badge-sm {{ $rs>0 ? 'badge-accent' : 'badge-ghost opacity-40' }}">
                          店 {{ $rs }}
                        </span>
                        {{-- 配達（delivery） --}}
                        <span class="badge badge-sm {{ $rd>0 ? 'badge-info' : 'badge-ghost opacity-40' }}">
                          配 {{ $rd }}
                        </span>
                      </div>

                      <span class="text-[11px] opacity-70 selected-mark hidden mt-auto">選択中</span>
                    </button>
                  @endforeach
                </div>
              @endforeach
            </div>
          </div>

          <div class="mt-3">
            <div class="badge badge-outline mr-2">カレンダーをクリックして日付を選択</div>
            <div class="badge">当日枠は青枠</div>
          </div>
        </div>
      </div>
    </div>

    {{-- 右：予約フォーム --}}
    <div>
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h3 class="card-title">予約フォーム</h3>

          @if ($errors->any())
            <div class="alert alert-error">
              <ul class="list-disc ml-4">
                @foreach ($errors->all() as $e)
                  <li>{{ $e }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form method="POST" action="{{ route('reserve.store') }}" id="reserveForm" class="space-y-4">
            @csrf

            {{-- hidden 実体（カレンダークリックで入る） --}}
            <input type="hidden" name="date" id="date">

            <div class="form-control">
              <label class="label"><span class="label-text">枠タイプ</span></label>
              <select name="slot_type" id="slot_type" class="select select-bordered" required>
                <option value="store" selected>来店（店頭対応）</option>
                <option value="delivery">配達</option>
              </select>
            </div>

            <div class="form-control">
              <label class="label"><span class="label-text">空き枠</span></label>
              <select name="slot_id" id="slot_id" class="select select-bordered" required>
                <option value="">カレンダーで日付と枠タイプを選んでください</option>
              </select>
            </div>

            {{-- ゲスト情報 --}}
            <div class="grid gap-4 md:grid-cols-2">
              <div class="form-control">
                <label class="label"><span class="label-text">お名前</span></label>
                <input type="text" name="guest_name" class="input input-bordered" required>
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text">電話番号</span></label>
                <input type="tel" name="guest_phone" class="input input-bordered" required>
              </div>
            </div>

            {{-- 商品・数量 --}}
            <div class="grid gap-4 md:grid-cols-3">
              <div class="form-control md:col-span-2">
                <label class="label"><span class="label-text">商品</span></label>
                <select name="product_id" id="product_id" class="select select-bordered" required>
                  <option value="">選択してください</option>
                  @foreach(\App\Models\Product::where('is_active', true)->orderBy('name')->get() as $prod)
                    <option value="{{ $prod->id }}" data-price="{{ $prod->price }}">
                      {{ $prod->name }} (¥{{ number_format($prod->price) }})
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text">数量</span></label>
                <input type="number" name="quantity" id="quantity" min="1" value="1" class="input input-bordered" required>
              </div>
              <div class="form-control md:col-span-3">
                <label class="label"><span class="label-text">合計金額</span></label>
                <input type="text" id="total_amount_preview" class="input input-bordered" readonly>
              </div>
            </div>

            {{-- 配達先（配達時のみ） --}}
            <div id="deliveryFields" class="grid gap-4 md:grid-cols-3 hidden">
              <div class="form-control">
                <label class="label"><span class="label-text">配送エリア</span></label>
                <select name="delivery_area" id="delivery_area" class="select select-bordered">
                  <option value="">エリアを選択</option>
                  <option value="namie">浪江町</option>
                  <option value="futaba">双葉町</option>
                  <option value="okuma">大熊町</option>
                  <option value="odaka">南相馬市小高区</option>
                </select>
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text">郵便番号</span></label>
                <input type="text" name="delivery_postal_code" id="delivery_postal_code"
                       class="input input-bordered" placeholder="979-xxxx">
              </div>
              <div class="form-control">
                <label class="label"><span class="label-text">住所</span></label>
                <input type="text" name="delivery_address" id="delivery_address"
                       class="input input-bordered" placeholder="○○町△△番地…">
              </div>
            </div>

            <div class="form-control">
              <label class="label"><span class="label-text">備考</span></label>
              <textarea name="notes" class="textarea textarea-bordered" rows="3" placeholder="メッセージカード内容など"></textarea>
            </div>

            <div class="card-actions justify-end">
              <button class="btn btn-primary w-full">予約を確定する</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- JS：カレンダー→日付選択、枠読み込み、合計金額、自動必須切替 --}}
  <script>
    const dateHidden = document.getElementById('date');
    const dayCells = document.querySelectorAll('.daycell');
    const typeEl = document.getElementById('slot_type');
    const slotEl = document.getElementById('slot_id');
    const prodEl = document.getElementById('product_id');
    const qtyEl  = document.getElementById('quantity');
    const totalEl = document.getElementById('total_amount_preview');
    const deliveryWrap = document.getElementById('deliveryFields');

    let selectedBtn = null;

    function toggleDeliveryFields() {
      const isDelivery = typeEl.value === 'delivery';
      deliveryWrap.classList.toggle('hidden', !isDelivery);
      ['delivery_area','delivery_postal_code','delivery_address'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.required = isDelivery;
      });
    }
    typeEl.addEventListener('change', () => { toggleDeliveryFields(); loadSlots(); });
    toggleDeliveryFields();

    async function loadSlots() {
      slotEl.innerHTML = '<option value="">読み込み中...</option>';
      const d = dateHidden.value;
      const t = typeEl.value;
      if (!d || !t) { slotEl.innerHTML = '<option value="">日付と枠タイプを選んでください</option>'; return; }

      const res = await fetch(`/slots?date=${encodeURIComponent(d)}&slot_type=${encodeURIComponent(t)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();
      if (!Array.isArray(data) || data.length === 0) {
        slotEl.innerHTML = '<option value="">空き枠なし</option>';
        return;
      }
      slotEl.innerHTML = '<option value="">選択してください</option>';
      for (const s of data) {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `${s.start_time} - ${s.end_time}（残り${s.remaining}）`;
        slotEl.appendChild(opt);
      }
    }

    function updateTotal() {
      const opt = prodEl.selectedOptions[0];
      const price = opt ? Number(opt.dataset.price || 0) : 0;
      const qty = Number(qtyEl.value || 0);
      totalEl.value = (price * qty).toLocaleString('ja-JP', { style: 'currency', currency: 'JPY' });
    }
    prodEl.addEventListener('change', updateTotal);
    qtyEl.addEventListener('input', updateTotal);
    updateTotal();

    dayCells.forEach(btn => {
      btn.addEventListener('click', () => {
        const date = btn.getAttribute('data-date');
        dateHidden.value = date;

        if (selectedBtn) selectedBtn.querySelector('.selected-mark')?.classList.add('hidden');
        selectedBtn = btn;
        btn.querySelector('.selected-mark')?.classList.remove('hidden');

        loadSlots();
        window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
      });
    });
  </script>
@endsection
