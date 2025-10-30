<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        User::insert([
            ['name' => '管理者', 'email' => 'admin@gmail.com', 'password' => Hash::make('password'), 'role' => 'admin', 'email_verified_at' => $now,],
            ['name' => 'スタッフ1', 'email' => 'staff1@gmail.com', 'password' => Hash::make('password'), 'role' => 'staff', 'email_verified_at' => $now,],
            ['name' => 'スタッフ2', 'email' => 'staff2@gmail.com', 'password' => Hash::make('password'), 'role' => 'staff', 'email_verified_at' => $now,],
            ['name' => 'スタッフ3', 'email' => 'staff3@gmail.com', 'password' => Hash::make('password'), 'role' => 'staff', 'email_verified_at' => $now,],
            ['name' => 'スタッフ4', 'email' => 'staff4@gmail.com', 'password' => Hash::make('password'), 'role' => 'staff', 'email_verified_at' => $now,],
        ]);
    }
}
