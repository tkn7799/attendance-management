<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->role !== 1) {
            if (Auth::check()) {
                Auth::logout();
            }

            return redirect('/admin/login')->withErrors('アクセス権限がありません。');
        }

        return $next($request);
    }
}
