<!DOCTYPE html>
<html lang="ja" data-theme="cupcake"> {{-- daisyUI テーマ --}}
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title','Reservation')</title>

  {{-- Tailwind CDN + daisyUI CDN --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { container: { center: true } } } }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/daisyui@latest/dist/full.js"></script>
</head>
<body class="min-h-screen bg-base-200">
  <div class="navbar bg-base-100 shadow">
    <div class="flex-1">
      <a href="/" class="btn btn-ghost text-xl">Namie Flower</a>
    </div>
    <div class="flex-none">
      <a href="{{ route('reserve.create') }}" class="btn btn-primary btn-sm">New Reservation</a>
      @auth
        <form method="POST" action="{{ route('logout') }}" class="ml-2">
          @csrf
          <button class="btn btn-ghost btn-sm">Logout</button>
        </form>
      @endauth
    </div>
  </div>

  <main class="container px-4 py-6">
    @yield('content')
  </main>
</body>
</html>
