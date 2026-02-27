<?php

namespace Tests\Feature\Api;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $team = Team::create([
            'owner_id' => $user->id,
            'name' => 'Jane Team',
            'slug' => 'jane-team',
            'plan_type' => 'free',
        ]);

        $team->members()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_team_id' => $team->id]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'current_team' => [
                'id' => $team->id,
                'name' => 'Jane Team',
                'plan_type' => 'free',
                'subscribed' => false,
            ],
        ]);
    }

    public function test_can_get_authenticated_user_with_subscription(): void
    {
        $user = User::factory()->create();

        $team = Team::create([
            'owner_id' => $user->id,
            'name' => 'Pro Team',
            'slug' => 'pro-team',
            'plan_type' => 'individual',
        ]);

        $team->members()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_team_id' => $team->id]);

        $subscription = $team->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_' . Str::random(10),
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'quantity' => 1,
        ]);

        $subscription->items()->create([
            'stripe_id' => 'si_test_' . Str::random(10),
            'stripe_product' => 'prod_test',
            'stripe_price' => 'price_test',
            'quantity' => 1,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonPath('current_team.subscribed', true);
        $response->assertJsonPath('current_team.plan_type', 'individual');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }
}
