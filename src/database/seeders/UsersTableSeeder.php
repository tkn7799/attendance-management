<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'テストユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password123'),
            'role' => 2,
            'email_verified_at' => Carbon::now(),
    ]);
        User::create([
            'name' => 'テストユーザー2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password123'),
            'role' => 2,
            'email_verified_at' => Carbon::now(),
    ]);
    }
}
