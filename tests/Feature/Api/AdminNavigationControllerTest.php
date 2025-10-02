<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_endpoint_returns_menu_bucket_with_expanded_dashboard_children(): void
    {
        $user = User::factory()->create();
        $user->user_type = 'admin';
        $user->save();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/navigation/admin');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.buckets.0.id', 'menu');
        $response->assertJsonPath('data.buckets.0.items.0.id', 'dashboard');
        $response->assertJsonPath('data.buckets.0.items.0.expanded', true);
        $response->assertJsonCount(3, 'data.buckets.0.items.0.children');

        $response->assertJsonPath('data.buckets.0.items.0.children.0.id', 'dashboard_overview');
        $response->assertJsonPath('data.buckets.0.items.0.children.1.id', 'dashboard_analytics');
        $response->assertJsonPath('data.buckets.0.items.0.children.2.id', 'dashboard_reports');
    }
}
