<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Namie Flower')</title>

  <!-- Tailwind + daisyUI CDN（開発用） -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.js"></script>
</head>
<body class="bg-base-200 min-h-screen">
  <div class="navbar bg-base-100 shadow">
    <div class="container mx-auto px-4">
      <a href="/" class="btn btn-ghost text-xl">Namie Flower</a>
      <div class="ml-auto">
        <a href="{{ route('products.index') }}" class="btn btn-sm">商品一覧</a>
      </div>
    </div>
  </div>

  <main class="container mx-auto px-4 py-6">
    @yield('content')
  </main>
</body>
</html>
