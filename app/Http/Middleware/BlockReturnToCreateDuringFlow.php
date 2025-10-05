<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockReturnToCreateDuringFlow
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('reserve.create')
            && $request->session()->boolean('reservation.flow_locked')) {
            return redirect()
                ->route('products.index')
                ->with('status', '現在選択した日時・受取り方法にて予約手続きを進めています。完了後に「別の日程で新しく予約する」からやり直してください。');
        }

        return $next($request);
    }
}
