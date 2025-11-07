<?php

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserClientScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_clients_scope_returns_only_client_users()
    {
        $client = User::factory()->create(['user_type' => UserType::MERCHANT]);
        $admin = User::factory()->create(['user_type' => UserType::ADMIN]);
        $delivery = User::factory()->create(['user_type' => UserType::DELIVERYMAN]);

        $clientIds = User::clients()->pluck('id');

        $this->assertTrue($clientIds->contains($client->id));
        $this->assertFalse($clientIds->contains($admin->id));
        $this->assertFalse($clientIds->contains($delivery->id));
    }

    public function test_user_type_aliases_are_normalized_to_client_type()
    {
        $user = User::factory()->create(['user_type' => UserType::ADMIN]);

        $user->user_type = 'client';
        $user->save();

        $user->refresh();

        $this->assertSame(UserType::MERCHANT, $user->user_type);
        $this->assertTrue($user->is_client);
        $this->assertSame('client', $user->user_type_label);
    }
}
