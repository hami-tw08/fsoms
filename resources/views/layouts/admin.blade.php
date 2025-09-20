<!doctype html>
<html lang="ja" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Admin') | Namie Flower OMS</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen">
  <div class="drawer lg:drawer-open">
    <input id="admin-drawer" type="checkbox" class="drawer-toggle"/>
    <div class="drawer-content p-4">
      <div class="navbar bg-base-100 shadow mb-4 rounded-2xl">
        <div class="flex-none lg:hidden">
          <label for="admin-drawer" class="btn btn-ghost btn-square">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round"
                 stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </label>
        </div>
        <div class="flex-1">
          <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost text-xl">OMS Admin</a>
        </div>
        <div class="flex-none">
          <span class="mr-3">{{ auth()->user()->name ?? 'Admin' }}</span>
          <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="btn btn-outline btn-sm">Logout</button>
          </form>
        </div>
      </div>

      @if (session('status'))
        <div class="alert alert-success mb-4">{{ session('status') }}</div>
      @endif

      @yield('content')
    </div>
    <div class="drawer-side">
      <label for="admin-drawer" class="drawer-overlay"></label>
      <aside class="menu p-4 w-72 min-h-full bg-base-200">
        <h2 class="mb-4 text-lg font-semibold">メニュー</h2>
        <ul>
          <li><a href="{{ route('admin.dashboard') }}">ダッシュボード</a></li>
          <li><a href="{{ route('admin.reservations.index') }}">予約一覧</a></li>
          <li><a href="{{ route('admin.slots.index') }}">枠（店/配）</a></li>
          {{-- 今後：商品/在庫/配達エリア管理など --}}
        </ul>
      </aside>
    </div>
  </div>
</body>
</html>