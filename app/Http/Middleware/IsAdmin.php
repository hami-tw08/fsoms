<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // ここに来る時点で auth 済み（未ログインは既にリダイレクト済み）
        if (!($request->user()->is_admin ?? false)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
