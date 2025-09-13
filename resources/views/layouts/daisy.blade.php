<!DOCTYPE html>
<html lang="ja" data-theme="cupcake"> {{-- daisyUI テーマ --}}
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', config('app.name'))</title>

  {{-- Tailwind + daisyUI（CSS版） --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { container: { center: true } } } }
  </script>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" />
</head>
<body class="min-h-screen bg-base-200">
  <div class="navbar bg-base-100 shadow">
    <div class="flex-1">
      {{-- ブランド名：やや大きく（タイトル > キャッチ） --}}
      <a href="{{ url('/') }}" class="btn btn-ghost normal-case px-2 text-2xl md:text-3xl font-extrabold tracking-tight">
        {{ config('app.name') }}
      </a>
    </div>
    <div class="flex-none">
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
