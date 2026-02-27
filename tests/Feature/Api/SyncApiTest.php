<?php

namespace Tests\Feature\Api;

use App\Models\Persona;
use App\Models\Script;
use App\Models\Site;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SyncApiTest extends TestCase
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
            'plan_type' => 'individual',
        ]);

        $this->team->members()->attach($this->user->id, ['role' => 'owner']);
        $this->user->update(['current_team_id' => $this->team->id]);

        // Subscribe the team by default for sync tests (sync requires active subscription)
        $this->subscribeTeam($this->team);
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

    public function test_sync_requires_active_subscription(): void
    {
        // Create an unsubscribed user + team
        $freeUser = User::factory()->create();
        $freeTeam = Team::create([
            'owner_id' => $freeUser->id,
            'name' => 'Free Team',
            'slug' => 'free-team',
            'plan_type' => 'free',
        ]);
        $freeTeam->members()->attach($freeUser->id, ['role' => 'owner']);
        $freeUser->update(['current_team_id' => $freeTeam->id]);

        Sanctum::actingAs($freeUser, ['*']);

        $response = $this->postJson('/api/sync', [
            'last_synced_at' => null,
            'scripts' => [],
            'sites' => [],
            'personas' => [],
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Active subscription required.']);
    }

    public function test_sync_uploads_new_scripts(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $scriptId = Str::uuid()->toString();

        $response = $this->postJson('/api/sync', [
            'last_synced_at' => null,
            'scripts' => [
                [
                    'id' => $scriptId,
                    'name' => 'Synced Script',
                    'fields' => [['selector' => '#email', 'value' => 'sync@test.com']],
                    'version' => 1,
                    'site_id' => null,
                    'persona_id' => null,
                    'url_hint' => 'https://example.com/form',
                    'created_by_name' => 'Test User',
                    'updated_at' => now()->toIso8601String(),
                    'deleted_at' => null,
                ],
            ],
            'sites' => [],
            'personas' => [],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'synced_at',
            'scripts',
            'sites',
            'personas',
        ]);

        $this->assertDatabaseHas('scripts', [
            'id' => $scriptId,
            'name' => 'Synced Script',
            'team_id' => $this->team->id,
        ]);

        // The newly created script should appear in the response
        $response->assertJsonFragment(['id' => $scriptId]);
    }

    public function test_sync_uploads_new_sites(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $siteId = Str::uuid()->toString();

        $response = $this->postJson('/api/sync', [
            'last_synced_at' => null,
            'sites' => [
                [
                    'id' => $siteId,
                    'hostname' => 'synced-site.com',
                    'label' => 'Synced Site',
                    'updated_at' => now()->toIso8601String(),
                    'deleted_at' => null,
                ],
            ],
            'scripts' => [],
            'personas' => [],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sites', [
            'id' => $siteId,
            'hostname' => 'synced-site.com',
            'team_id' => $this->team->id,
        ]);

        $response->assertJsonFragment(['id' => $siteId]);
    }

    public function test_sync_uploads_new_personas(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $personaId = Str::uuid()->toString();

        $response = $this->postJson('/api/sync', [
            'last_synced_at' => null,
            'personas' => [
                [
                    'id' => $personaId,
                    'site_id' => null,
                    'name' => 'Synced Persona',
                    'updated_at' => now()->toIso8601String(),
                    'deleted_at' => null,
                ],
            ],
            'scripts' => [],
            'sites' => [],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('personas', [
            'id' => $personaId,
            'name' => 'Synced Persona',
            'team_id' => $this->team->id,
        ]);

        $response->assertJsonFragment(['id' => $personaId]);
    }

    public function test_sync_downloads_server_changes(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        // Simulate a past sync time
        $lastSyncedAt = Carbon::now()->subHour();

        // Create server-side records AFTER the last sync time
        $script = Script::create([
            'id' => Str::uuid()->toString(),
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'name' => 'Server Script',
            'fields' => [['selector' => '#field', 'value' => 'val']],
            'version' => 1,
        ]);

        $site = Site::create([
            'id' => Str::uuid()->toString(),
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'hostname' => 'server-site.com',
            'label' => 'Server Site',
        ]);

        $persona = Persona::create([
            'id' => Str::uuid()->toString(),
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'name' => 'Server Persona',
        ]);

        $response = $this->postJson('/api/sync', [
            'last_synced_at' => $lastSyncedAt->toIso8601String(),
            'scripts' => [],
            'sites' => [],
            'personas' => [],
        ]);

        $response->assertStatus(200);

        // All server-created records should be returned
        $response->assertJsonFragment(['name' => 'Server Script']);
        $response->assertJsonFragment(['hostname' => 'server-site.com']);
        $response->assertJsonFragment(['name' => 'Server Persona']);
    }

    public function test_sync_handles_soft_deleted_records(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $lastSyncedAt = Carbon::now()->subHour();

        // Create a script and then soft-delete it
        $script = Script::create([
            'id' => Str::uuid()->toString(),
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'name' => 'Deleted Script',
            'fields' => [['selector' => '#x', 'value' => 'y']],
            'version' => 1,
        ]);
        $script->delete();

        // Create a site and then soft-delete it
        $site = Site::create([
            'id' => Str::uuid()->toString(),
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'hostname' => 'deleted-site.com',
        ]);
        $site->delete();

        $response = $this->postJson('/api/sync', [
            'last_synced_at' => $lastSyncedAt->toIso8601String(),
            'scripts' => [],
            'sites' => [],
            'personas' => [],
        ]);

        $response->assertStatus(200);

        // Soft-deleted records should be returned with their deleted_at timestamp
        $scripts = collect($response->json('scripts'));
        $deletedScript = $scripts->firstWhere('id', $script->id);
        $this->assertNotNull($deletedScript, 'Deleted script should appear in sync response');
        $this->assertNotNull($deletedScript['deleted_at'], 'deleted_at should be set for soft-deleted script');

        $sites = collect($response->json('sites'));
        $deletedSite = $sites->firstWhere('id', $site->id);
        $this->assertNotNull($deletedSite, 'Deleted site should appear in sync response');
        $this->assertNotNull($deletedSite['deleted_at'], 'deleted_at should be set for soft-deleted site');
    }

    public function test_sync_last_write_wins(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        // Create a script on the server with a recent updated_at
        $serverUpdatedAt = Carbon::now();
        $scriptId = Str::uuid()->toString();

        $serverScript = Script::create([
            'id' => $scriptId,
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'name' => 'Server Version',
            'fields' => [['selector' => '#server', 'value' => 'server-value']],
            'version' => 2,
        ]);

        // Force the updated_at to a known recent time
        Script::where('id', $scriptId)->update(['updated_at' => $serverUpdatedAt]);

        // Send a sync with an OLDER updated_at for the same script
        $clientUpdatedAt = $serverUpdatedAt->copy()->subMinutes(10);

        $response = $this->postJson('/api/sync', [
            'last_synced_at' => null,
            'scripts' => [
                [
                    'id' => $scriptId,
                    'name' => 'Client Version',
                    'fields' => [['selector' => '#client', 'value' => 'client-value']],
                    'version' => 1,
                    'site_id' => null,
                    'persona_id' => null,
                    'url_hint' => null,
                    'created_by_name' => null,
                    'updated_at' => $clientUpdatedAt->toIso8601String(),
                    'deleted_at' => null,
                ],
            ],
            'sites' => [],
            'personas' => [],
        ]);

        $response->assertStatus(200);

        // The server should keep its newer version (last-write-wins)
        $this->assertDatabaseHas('scripts', [
            'id' => $scriptId,
            'name' => 'Server Version',
        ]);

        $this->assertDatabaseMissing('scripts', [
            'id' => $scriptId,
            'name' => 'Client Version',
        ]);

        // The response should contain the server's version
        $scripts = collect($response->json('scripts'));
        $returnedScript = $scripts->firstWhere('id', $scriptId);
        $this->assertNotNull($returnedScript);
        $this->assertEquals('Server Version', $returnedScript['name']);
    }

    public function test_sync_client_wins_when_newer(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        // Create a script on the server with an older updated_at
        $scriptId = Str::uuid()->toString();
        $serverUpdatedAt = Carbon::now()->subHour();

        $serverScript = Script::create([
            'id' => $scriptId,
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'name' => 'Server Version',
            'fields' => [['selector' => '#server', 'value' => 'server-value']],
            'version' => 1,
        ]);

        // Force the updated_at to an older time
        Script::where('id', $scriptId)->update(['updated_at' => $serverUpdatedAt]);

        // Send a sync with a NEWER updated_at for the same script
        $clientUpdatedAt = Carbon::now();

        $response = $this->postJson('/api/sync', [
            'last_synced_at' => null,
            'scripts' => [
                [
                    'id' => $scriptId,
                    'name' => 'Client Version',
                    'fields' => [['selector' => '#client', 'value' => 'client-value']],
                    'version' => 2,
                    'site_id' => null,
                    'persona_id' => null,
                    'url_hint' => null,
                    'created_by_name' => null,
                    'updated_at' => $clientUpdatedAt->toIso8601String(),
                    'deleted_at' => null,
                ],
            ],
            'sites' => [],
            'personas' => [],
        ]);

        $response->assertStatus(200);

        // The client's newer version should overwrite the server's
        $this->assertDatabaseHas('scripts', [
            'id' => $scriptId,
            'name' => 'Client Version',
        ]);
    }

    public function test_sync_handles_deleted_record_from_client(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        // Create a script on the server
        $scriptId = Str::uuid()->toString();
        $serverUpdatedAt = Carbon::now()->subHour();

        Script::create([
            'id' => $scriptId,
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'name' => 'To Be Deleted',
            'fields' => [['selector' => '#x', 'value' => 'y']],
            'version' => 1,
        ]);

        Script::where('id', $scriptId)->update(['updated_at' => $serverUpdatedAt]);

        // Client sends a delete with a newer timestamp
        $clientUpdatedAt = Carbon::now();

        $response = $this->postJson('/api/sync', [
            'last_synced_at' => null,
            'scripts' => [
                [
                    'id' => $scriptId,
                    'name' => 'To Be Deleted',
                    'fields' => [['selector' => '#x', 'value' => 'y']],
                    'version' => 1,
                    'site_id' => null,
                    'persona_id' => null,
                    'url_hint' => null,
                    'created_by_name' => null,
                    'updated_at' => $clientUpdatedAt->toIso8601String(),
                    'deleted_at' => $clientUpdatedAt->toIso8601String(),
                ],
            ],
            'sites' => [],
            'personas' => [],
        ]);

        $response->assertStatus(200);

        // The script should now be soft-deleted
        $this->assertSoftDeleted('scripts', ['id' => $scriptId]);
    }
}
