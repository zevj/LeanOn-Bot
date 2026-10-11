<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

require_once __DIR__ . '/LocalTestingSeeder.php';

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('local', 'testing')) {
            $this->call([
                LocalTestingSeeder::class,
            ]);
        }
    }
}
