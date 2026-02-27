<?php

namespace Tests\Feature\Api;

use App\Models\Site;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SiteApiTest extends TestCase
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

    private function createSite(array $overrides = []): Site
    {
        return Site::create(array_merge([
            'id' => Str::uuid()->toString(),
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'hostname' => 'example.com',
            'label' => 'Example Site',
        ], $overrides));
    }

    public function test_can_list_team_sites(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $site1 = $this->createSite(['hostname' => 'alpha.com', 'label' => 'Alpha']);
        $site2 = $this->createSite(['hostname' => 'beta.com', 'label' => 'Beta']);

        $response = $this->getJson('/api/sites');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['hostname' => 'alpha.com']);
        $response->assertJsonFragment(['hostname' => 'beta.com']);
    }

    public function test_can_create_site(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $payload = [
            'id' => Str::uuid()->toString(),
            'hostname' => 'newsite.com',
            'label' => 'New Site',
        ];

        $response = $this->postJson('/api/sites', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.hostname', 'newsite.com');
        $response->assertJsonPath('data.label', 'New Site');

        $this->assertDatabaseHas('sites', [
            'hostname' => 'newsite.com',
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_show_site(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $site = $this->createSite(['hostname' => 'show.com', 'label' => 'Show Me']);

        $response = $this->getJson("/api/sites/{$site->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $site->id);
        $response->assertJsonPath('data.hostname', 'show.com');
        $response->assertJsonPath('data.label', 'Show Me');
    }

    public function test_can_update_site(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $site = $this->createSite(['hostname' => 'old.com', 'label' => 'Old']);

        $response = $this->putJson("/api/sites/{$site->id}", [
            'hostname' => 'updated.com',
            'label' => 'Updated',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.hostname', 'updated.com');
        $response->assertJsonPath('data.label', 'Updated');

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'hostname' => 'updated.com',
            'label' => 'Updated',
        ]);
    }

    public function test_can_delete_site(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $site = $this->createSite();

        $response = $this->deleteJson("/api/sites/{$site->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Deleted.');

        $this->assertSoftDeleted('sites', ['id' => $site->id]);
    }

    public function test_cannot_access_other_teams_site(): void
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

        $otherSite = Site::create([
            'id' => Str::uuid()->toString(),
            'team_id' => $otherTeam->id,
            'user_id' => $otherUser->id,
            'hostname' => 'secret.com',
            'label' => 'Secret',
        ]);

        Sanctum::actingAs($this->user, ['*']);

        // Viewing another team's site should be forbidden
        $response = $this->getJson("/api/sites/{$otherSite->id}");
        $response->assertStatus(403);

        // Updating another team's site should be forbidden
        $response = $this->putJson("/api/sites/{$otherSite->id}", [
            'hostname' => 'hacked.com',
        ]);
        $response->assertStatus(403);

        // Deleting another team's site should be forbidden
        $response = $this->deleteJson("/api/sites/{$otherSite->id}");
        $response->assertStatus(403);
    }
}
