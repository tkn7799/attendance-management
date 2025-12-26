<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    // スタッフ一覧表示
    public function index()
    {
        $staffs = User::where('role', User::ROLE_USER)->get();
        return view('admin.staff.list', compact('staffs'));
    }
}
