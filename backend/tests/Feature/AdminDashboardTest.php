<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\ChatMessage;
use App\Models\SessionLog;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard_stats()
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'guidance',
            'email_verified_at' => now(),
            'terms_accepted_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        // Create some dummy data
        ChatMessage::create([
            'user_id' => $admin->id,
            'message' => 'Hello',
            'reply' => 'Hi!',
            'is_crisis' => false
        ]);
        SessionLog::create([
            'user_id' => $admin->id,
            'session_start' => now()->subMinutes(10),
            'session_end' => now(),
        ]);

        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_interactions',
                'active_users',
                'avg_session_minutes',
                'daily_interactions',
                'monthly_interactions',
            ]);
    }
}
