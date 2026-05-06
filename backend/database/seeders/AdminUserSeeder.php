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
        User::updateOrCreate(
            ['email' => '123456789@gordoncollege.edu.ph'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'password' => Hash::make('password123'),
                'role' => 'guidance',
                'email_verified_at' => now(),
            ]
        );
    }
}
