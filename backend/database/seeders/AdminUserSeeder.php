<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => '123456789@gordoncollege.edu.ph',
            'password' => Hash::make('password123'),
            'role' => 'guidance',
            'email_verified_at' => now(),
        ]);
    }
}
