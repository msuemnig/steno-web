<?php

namespace Tests\Unit\Models;

use App\Models\Persona;
use App\Models\Script;
use App\Models\Site;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    private function createTeamWithOwner(): array
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($owner, ['role' => 'owner']);

        return [$team, $owner];
    }

    public function test_team_has_owner_relationship(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();

        $this->assertNotNull($team->owner);
        $this->assertEquals($owner->id, $team->owner->id);
    }

    public function test_team_has_members_relationship(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();

        $editor = User::factory()->create();
        $team->members()->attach($editor, ['role' => 'editor']);

        $this->assertCount(2, $team->members);
        $this->assertTrue($team->members->contains($owner));
        $this->assertTrue($team->members->contains($editor));
    }

    public function test_team_has_scripts_relationship(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();

        $script = Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
        ]);

        $this->assertCount(1, $team->scripts);
        $this->assertTrue($team->scripts->contains($script));
    }

    public function test_team_has_sites_relationship(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();

        $site = Site::factory()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
        ]);

        $this->assertCount(1, $team->sites);
        $this->assertTrue($team->sites->contains($site));
    }

    public function test_team_has_personas_relationship(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();

        $persona = Persona::factory()->create([
            'team_id' => $team->id,
            'user_id' => $owner->id,
        ]);

        $this->assertCount(1, $team->personas);
        $this->assertTrue($team->personas->contains($persona));
    }

    public function test_team_has_invitations_relationship(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();

        $invitation = TeamInvitation::create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => 'editor',
            'token' => str_repeat('a', 64),
        ]);

        $this->assertCount(1, $team->invitations);
        $this->assertEquals('invited@example.com', $team->invitations->first()->email);
    }

    public function test_team_has_user_checks_membership(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $nonMember = User::factory()->create();

        $this->assertTrue($team->hasUser($owner));
        $this->assertFalse($team->hasUser($nonMember));
    }

    public function test_team_user_role_returns_role(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();

        $editor = User::factory()->create();
        $team->members()->attach($editor, ['role' => 'editor']);

        $this->assertEquals('owner', $team->userRole($owner));
        $this->assertEquals('editor', $team->userRole($editor));
    }

    public function test_team_user_role_returns_null_for_non_member(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $nonMember = User::factory()->create();

        $this->assertNull($team->userRole($nonMember));
    }

    public function test_team_is_personal(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();

        $this->assertTrue($team->isPersonal());
    }

    public function test_team_is_not_personal_with_multiple_members(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = User::factory()->create();
        $team->members()->attach($editor, ['role' => 'editor']);

        $this->assertFalse($team->isPersonal());
    }

    public function test_team_is_not_personal_on_business_plan(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create([
            'owner_id' => $owner->id,
            'plan_type' => 'business',
        ]);
        $team->members()->attach($owner, ['role' => 'owner']);

        $this->assertFalse($team->isPersonal());
    }
}
