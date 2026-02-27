<?php

namespace Tests\Unit\Models;

use App\Models\Persona;
use App\Models\Script;
use App\Models\Site;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScriptTest extends TestCase
{
    use RefreshDatabase;

    public function test_script_uses_uuid_primary_key(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $script->id
        );
        $this->assertFalse($script->getIncrementing());
        $this->assertEquals('string', $script->getKeyType());
    }

    public function test_script_has_soft_deletes(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $scriptId = $script->id;
        $script->delete();

        $this->assertSoftDeleted('scripts', ['id' => $scriptId]);
        $this->assertNull(Script::find($scriptId));
        $this->assertNotNull(Script::withTrashed()->find($scriptId));
    }

    public function test_script_belongs_to_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($script->team);
        $this->assertEquals($team->id, $script->team->id);
    }

    public function test_script_belongs_to_site(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $site = Site::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'site_id' => $site->id,
        ]);

        $this->assertNotNull($script->site);
        $this->assertEquals($site->id, $script->site->id);
    }

    public function test_script_belongs_to_persona(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $persona = Persona::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'persona_id' => $persona->id,
        ]);

        $this->assertNotNull($script->persona);
        $this->assertEquals($persona->id, $script->persona->id);
    }

    public function test_script_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($script->user);
        $this->assertEquals($user->id, $script->user->id);
    }

    public function test_script_casts_fields_to_array(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $fields = [
            ['selector' => '#name', 'value' => 'John Doe', 'type' => 'fill'],
            ['selector' => '#email', 'value' => 'john@example.com', 'type' => 'fill'],
            ['selector' => '.submit-btn', 'value' => '', 'type' => 'click'],
        ];

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'fields' => $fields,
        ]);

        $script->refresh();

        $this->assertIsArray($script->fields);
        $this->assertCount(3, $script->fields);
        $this->assertEquals('#name', $script->fields[0]['selector']);
        $this->assertEquals('fill', $script->fields[0]['type']);
        $this->assertEquals('click', $script->fields[2]['type']);
    }

    public function test_script_casts_version_to_integer(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'version' => 3,
        ]);

        $script->refresh();

        $this->assertIsInt($script->version);
        $this->assertEquals(3, $script->version);
    }

    public function test_script_site_is_nullable(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'site_id' => null,
        ]);

        $this->assertNull($script->site);
    }

    public function test_script_persona_is_nullable(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'persona_id' => null,
        ]);

        $this->assertNull($script->persona);
    }
}
