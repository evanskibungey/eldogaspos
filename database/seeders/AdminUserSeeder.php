<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Admin',
            'email' => 'salimeldogas@gmail.com',
            'password' => Hash::make('salim@321'),
            'role' => 'admin',
            'status' => 'active'
        ]);
    }
}