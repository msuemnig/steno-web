<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_member_role(): void
    {
        $owner = $this->createUserWithTeam();
        $member = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($member, $team, 'editor');

        $response = $this->actingAs($owner)->put("/teams/{$team->id}/members/{$member->id}", [
            'role' => 'admin',
        ]);

        $response->assertRedirect();

        $this->assertEquals('admin', $team->fresh()->userRole($member));
    }

    public function test_admin_can_update_member_role(): void
    {
        $owner = $this->createUserWithTeam();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($admin, $team, 'admin');
        $this->addUserToTeam($member, $team, 'viewer');

        $response = $this->actingAs($admin)->put("/teams/{$team->id}/members/{$member->id}", [
            'role' => 'editor',
        ]);

        $response->assertRedirect();

        $this->assertEquals('editor', $team->fresh()->userRole($member));
    }

    public function test_editor_cannot_update_member_role(): void
    {
        $owner = $this->createUserWithTeam();
        $editor = User::factory()->create();
        $member = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($editor, $team, 'editor');
        $this->addUserToTeam($member, $team, 'viewer');

        $response = $this->actingAs($editor)->put("/teams/{$team->id}/members/{$member->id}", [
            'role' => 'admin',
        ]);

        $response->assertForbidden();

        // Role should remain unchanged
        $this->assertEquals('viewer', $team->fresh()->userRole($member));
    }

    public function test_cannot_change_owner_role(): void
    {
        $owner = $this->createUserWithTeam();
        $admin = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($admin, $team, 'admin');

        // Attempt to change the owner's role (admin tries to demote owner)
        $response = $this->actingAs($admin)->put("/teams/{$team->id}/members/{$owner->id}", [
            'role' => 'editor',
        ]);

        $response->assertForbidden();

        // Owner role should remain unchanged
        $this->assertEquals('owner', $team->fresh()->userRole($owner));
    }

    public function test_owner_cannot_change_own_role(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $owner->currentTeam;

        $response = $this->actingAs($owner)->put("/teams/{$team->id}/members/{$owner->id}", [
            'role' => 'admin',
        ]);

        $response->assertForbidden();

        $this->assertEquals('owner', $team->fresh()->userRole($owner));
    }

    public function test_owner_can_remove_member(): void
    {
        $owner = $this->createUserWithTeam();
        $member = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($member, $team, 'editor');

        // Verify member is on the team
        $this->assertTrue($team->hasUser($member));

        $response = $this->actingAs($owner)->delete("/teams/{$team->id}/members/{$member->id}");

        $response->assertRedirect();

        // Member should no longer be on the team
        $this->assertFalse($team->fresh()->hasUser($member));
    }

    public function test_admin_can_remove_member(): void
    {
        $owner = $this->createUserWithTeam();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($admin, $team, 'admin');
        $this->addUserToTeam($member, $team, 'editor');

        $response = $this->actingAs($admin)->delete("/teams/{$team->id}/members/{$member->id}");

        $response->assertRedirect();
        $this->assertFalse($team->fresh()->hasUser($member));
    }

    public function test_editor_cannot_remove_other_member(): void
    {
        $owner = $this->createUserWithTeam();
        $editor = User::factory()->create();
        $member = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($editor, $team, 'editor');
        $this->addUserToTeam($member, $team, 'viewer');

        $response = $this->actingAs($editor)->delete("/teams/{$team->id}/members/{$member->id}");

        $response->assertForbidden();

        // Member should still be on the team
        $this->assertTrue($team->fresh()->hasUser($member));
    }

    public function test_member_can_remove_self(): void
    {
        $owner = $this->createUserWithTeam();
        $member = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($member, $team, 'editor');
        $member->update(['current_team_id' => $team->id]);

        $response = $this->actingAs($member)->delete("/teams/{$team->id}/members/{$member->id}");

        $response->assertRedirect();
        $this->assertFalse($team->fresh()->hasUser($member));

        // After removal, current_team_id should revert to personal team
        $member->refresh();
        $personalTeam = $member->personalTeam();
        if ($personalTeam) {
            $this->assertEquals($personalTeam->id, $member->current_team_id);
        }
    }

    public function test_member_cannot_remove_self_if_owner(): void
    {
        $owner = $this->createUserWithTeam();
        $team = $owner->currentTeam;

        $response = $this->actingAs($owner)->delete("/teams/{$team->id}/members/{$owner->id}");

        $response->assertForbidden();

        // Owner should still be on the team
        $this->assertTrue($team->fresh()->hasUser($owner));
    }

    public function test_removed_member_current_team_resets_to_personal(): void
    {
        $owner = $this->createUserWithTeam();
        $member = $this->createUserWithTeam();
        $team = $owner->currentTeam;
        $this->addUserToTeam($member, $team, 'editor');

        // Switch the member to the owner's team
        $member->update(['current_team_id' => $team->id]);

        $personalTeamId = $member->personalTeam()->id;

        $response = $this->actingAs($owner)->delete("/teams/{$team->id}/members/{$member->id}");

        $response->assertRedirect();

        $member->refresh();
        $this->assertEquals($personalTeamId, $member->current_team_id);
    }
}
