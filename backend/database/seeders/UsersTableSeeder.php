<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => '123456789@gordoncollege.edu.ph',
                'first_name' => 'Guidance',
                'last_name' => 'Officer',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('123456'),
                'role' => 'guidance',
                'department' => null,
                'program' => null,
                'year_level' => null,
                'phone_number' => null,
                'terms_accepted_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'email' => '202311473@gordoncollege.edu.ph',
                'first_name' => 'Ira Jacob',
                'last_name' => 'Javier',
                'email_verified_at' => null,
                'password' => Hash::make('123456'),
                'role' => 'student',
                'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology',
                'year_level' => '3rd Year',
                'phone_number' => null,
                'terms_accepted_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'email' => '202310636@gordoncollege.edu.ph',
                'first_name' => 'Allysa',
                'last_name' => 'Lingad',
                'email_verified_at' => null,
                'password' => Hash::make('123456'),
                'role' => 'student',
                'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology',
                'year_level' => '3rd Year',
                'phone_number' => null,
                'terms_accepted_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        foreach ($users as $user) {
            if (!DB::table('users')->where('email', $user['email'])->exists()) {
                DB::table('users')->insert($user);
            }
        }
    }
}
