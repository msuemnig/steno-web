<?php

namespace Tests\Feature\Api;

use App\Models\Script;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScriptApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->team = Team::create([
            'owner_id' => $this->user->id,
            'name' => 'Test Team',
            'slug' => 'test-team',
            'plan_type' => 'free',
        ]);

        $this->team->members()->attach($this->user->id, ['role' => 'owner']);
        $this->user->update(['current_team_id' => $this->team->id]);
    }

    private function createScript(array $overrides = []): Script
    {
        return Script::create(array_merge([
            'id' => Str::uuid()->toString(),
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'name' => 'Test Script',
            'fields' => [['selector' => '#email', 'value' => 'test@example.com']],
            'version' => 1,
        ], $overrides));
    }

    private function subscribeTeam(Team $team): void
    {
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
    }

    public function test_can_list_team_scripts(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $script1 = $this->createScript(['name' => 'Script One']);
        $script2 = $this->createScript(['name' => 'Script Two']);

        $response = $this->getJson('/api/scripts');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['name' => 'Script One']);
        $response->assertJsonFragment(['name' => 'Script Two']);
    }

    public function test_can_create_script(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $payload = [
            'id' => Str::uuid()->toString(),
            'name' => 'New Script',
            'fields' => [
                ['selector' => '#username', 'value' => 'johndoe'],
                ['selector' => '#password', 'value' => 'secret123'],
            ],
        ];

        $response = $this->postJson('/api/scripts', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'New Script']);

        $this->assertDatabaseHas('scripts', [
            'name' => 'New Script',
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_create_script_with_client_uuid(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $clientUuid = Str::uuid()->toString();

        $payload = [
            'id' => $clientUuid,
            'name' => 'UUID Script',
            'fields' => [['selector' => '#email', 'value' => 'test@example.com']],
        ];

        $response = $this->postJson('/api/scripts', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.id', $clientUuid);

        $this->assertDatabaseHas('scripts', [
            'id' => $clientUuid,
            'name' => 'UUID Script',
        ]);
    }

    public function test_can_show_script(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $script = $this->createScript(['name' => 'Show Me']);

        $response = $this->getJson("/api/scripts/{$script->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $script->id);
        $response->assertJsonPath('data.name', 'Show Me');
        $response->assertJsonCount(count($script->fields), 'data.fields');
    }

    public function test_can_update_script(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $script = $this->createScript(['name' => 'Old Name']);

        $response = $this->putJson("/api/scripts/{$script->id}", [
            'name' => 'Updated Name',
            'fields' => [['selector' => '#new', 'value' => 'updated']],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('scripts', [
            'id' => $script->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_script(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $script = $this->createScript();

        $response = $this->deleteJson("/api/scripts/{$script->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Deleted.');

        // Verify soft-deleted
        $this->assertSoftDeleted('scripts', ['id' => $script->id]);
    }

    public function test_cannot_access_other_teams_script(): void
    {
        $otherUser = User::factory()->create();
        $otherTeam = Team::create([
            'owner_id' => $otherUser->id,
            'name' => 'Other Team',
            'slug' => 'other-team',
            'plan_type' => 'free',
        ]);
        $otherTeam->members()->attach($otherUser->id, ['role' => 'owner']);
        $otherUser->update(['current_team_id' => $otherTeam->id]);

        $otherScript = Script::create([
            'id' => Str::uuid()->toString(),
            'team_id' => $otherTeam->id,
            'user_id' => $otherUser->id,
            'name' => 'Other Script',
            'fields' => [['selector' => '#x', 'value' => 'y']],
            'version' => 1,
        ]);

        Sanctum::actingAs($this->user, ['*']);

        // Viewing another team's script should be forbidden
        $response = $this->getJson("/api/scripts/{$otherScript->id}");
        $response->assertStatus(403);

        // Updating another team's script should be forbidden
        $response = $this->putJson("/api/scripts/{$otherScript->id}", [
            'name' => 'Hacked',
        ]);
        $response->assertStatus(403);

        // Deleting another team's script should be forbidden
        $response = $this->deleteJson("/api/scripts/{$otherScript->id}");
        $response->assertStatus(403);
    }

    public function test_free_tier_script_limit_enforced(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $maxScripts = config('steno.free_tier.max_scripts');

        // Create the maximum allowed scripts
        for ($i = 0; $i < $maxScripts; $i++) {
            $this->createScript(['name' => "Script {$i}"]);
        }

        // The next script should be rejected
        $response = $this->postJson('/api/scripts', [
            'id' => Str::uuid()->toString(),
            'name' => 'One Too Many',
            'fields' => [['selector' => '#x', 'value' => 'y']],
        ]);

        $response->assertStatus(403);
        $response->assertSee('Free plan allows up to');
        $response->assertSee('Upgrade to save more');
    }

    public function test_paid_tier_no_script_limit(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $this->subscribeTeam($this->team);

        $maxScripts = config('steno.free_tier.max_scripts');

        // Create more scripts than the free limit
        for ($i = 0; $i < $maxScripts; $i++) {
            $this->createScript(['name' => "Script {$i}"]);
        }

        // Subscribed teams should be able to create beyond the free limit
        $response = $this->postJson('/api/scripts', [
            'id' => Str::uuid()->toString(),
            'name' => 'Beyond Free Limit',
            'fields' => [['selector' => '#x', 'value' => 'y']],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Beyond Free Limit');
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/scripts');
        $response->assertStatus(401);

        $response = $this->postJson('/api/scripts', [
            'name' => 'Test',
            'fields' => [['selector' => '#x', 'value' => 'y']],
        ]);
        $response->assertStatus(401);
    }
}
