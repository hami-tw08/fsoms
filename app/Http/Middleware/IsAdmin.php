<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // 未ログインはログインへ
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 管理者でなければ403
        if (!($request->user()->is_admin ?? false)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
