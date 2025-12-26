<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\Facades\Auth;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // ログアウトする前のユーザーが管理者だったかどうかで遷移先を分ける
        // ※ログアウト処理後なので session から情報を取るか、URLで判断します

        $isAdmin = str_contains($request->header('referer'), 'admin');

        if ($isAdmin) {
            return redirect('/admin/login');
        }

        return redirect('/login');
    }
}