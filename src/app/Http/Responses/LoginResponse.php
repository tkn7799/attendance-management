<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Models\User;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $role = auth()->user()->role;

        if ($role === User::ROLE_ADMIN) {
            return redirect()->intended('/admin/attendance/list');
        }

        // 認証済みならマイページへ
        return redirect()->intended('/attendance');
    }
}