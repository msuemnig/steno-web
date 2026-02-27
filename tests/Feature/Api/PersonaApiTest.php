<?php

namespace Tests\Feature\Api;

use App\Models\Persona;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PersonaApiTest extends TestCase
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

    private function createPersona(array $overrides = []): Persona
    {
        return Persona::create(array_merge([
            'id' => Str::uuid()->toString(),
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
            'name' => 'Test Persona',
        ], $overrides));
    }

    public function test_can_list_team_personas(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $persona1 = $this->createPersona(['name' => 'Alice']);
        $persona2 = $this->createPersona(['name' => 'Bob']);

        $response = $this->getJson('/api/personas');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['name' => 'Alice']);
        $response->assertJsonFragment(['name' => 'Bob']);
    }

    public function test_can_create_persona(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $payload = [
            'id' => Str::uuid()->toString(),
            'name' => 'New Persona',
        ];

        $response = $this->postJson('/api/personas', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'New Persona');

        $this->assertDatabaseHas('personas', [
            'name' => 'New Persona',
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_show_persona(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $persona = $this->createPersona(['name' => 'Show Me']);

        $response = $this->getJson("/api/personas/{$persona->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $persona->id);
        $response->assertJsonPath('data.name', 'Show Me');
    }

    public function test_can_update_persona(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $persona = $this->createPersona(['name' => 'Old Name']);

        $response = $this->putJson("/api/personas/{$persona->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('personas', [
            'id' => $persona->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_persona(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $persona = $this->createPersona();

        $response = $this->deleteJson("/api/personas/{$persona->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Deleted.');

        $this->assertSoftDeleted('personas', ['id' => $persona->id]);
    }

    public function test_cannot_access_other_teams_persona(): void
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

        $otherPersona = Persona::create([
            'id' => Str::uuid()->toString(),
            'team_id' => $otherTeam->id,
            'user_id' => $otherUser->id,
            'name' => 'Secret Persona',
        ]);

        Sanctum::actingAs($this->user, ['*']);

        // Viewing another team's persona should be forbidden
        $response = $this->getJson("/api/personas/{$otherPersona->id}");
        $response->assertStatus(403);

        // Updating another team's persona should be forbidden
        $response = $this->putJson("/api/personas/{$otherPersona->id}", [
            'name' => 'Hacked',
        ]);
        $response->assertStatus(403);

        // Deleting another team's persona should be forbidden
        $response = $this->deleteJson("/api/personas/{$otherPersona->id}");
        $response->assertStatus(403);
    }
}
