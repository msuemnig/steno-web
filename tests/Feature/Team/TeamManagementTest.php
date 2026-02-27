<?php

namespace Tests\Feature\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_team(): void
    {
        $user = $this->createUserWithTeam();

        $response = $this->actingAs($user)->post('/teams', [
            'name' => 'Acme Corp',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('teams', [
            'name' => 'Acme Corp',
            'owner_id' => $user->id,
            'plan_type' => 'free',
        ]);

        $team = Team::where('name', 'Acme Corp')->first();
        $this->assertNotNull($team);

        // The user should be attached as owner
        $this->assertTrue($team->hasUser($user));
        $this->assertEquals('owner', $team->userRole($user));

        // The user's current team should have switched to the new team
        $user->refresh();
        $this->assertEquals($team->id, $user->current_team_id);
    }

    public function test_user_can_view_own_team(): void
    {
        $user = $this->createUserWithTeam();
        $team = $user->currentTeam;

        $response = $this->actingAs($user)->get("/teams/{$team->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Teams/Show')
            ->has('team')
            ->has('members')
            ->has('invitations')
            ->has('isOwner')
        );
    }

    public function test_user_cannot_view_other_team(): void
    {
        $user = $this->createUserWithTeam();
        $otherUser = $this->createUserWithTeam();
        $otherTeam = $otherUser->currentTeam;

        $response = $this->actingAs($user)->get("/teams/{$otherTeam->id}");

        $response->assertForbidden();
    }

    public function test_owner_can_update_team(): void
    {
        $user = $this->createUserWithTeam();
        $team = $user->currentTeam;

        $response = $this->actingAs($user)->put("/teams/{$team->id}", [
            'name' => 'Updated Team Name',
        ]);

        $response->assertRedirect();

        $team->refresh();
        $this->assertEquals('Updated Team Name', $team->name);
    }

    public function test_admin_can_update_team(): void
    {
        $owner = $this->createUserWithTeam();
        $admin = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($admin, $team, 'admin');

        $response = $this->actingAs($admin)->put("/teams/{$team->id}", [
            'name' => 'Admin Updated Name',
        ]);

        $response->assertRedirect();

        $team->refresh();
        $this->assertEquals('Admin Updated Name', $team->name);
    }

    public function test_editor_cannot_update_team(): void
    {
        $owner = $this->createUserWithTeam();
        $editor = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($editor, $team, 'editor');

        $response = $this->actingAs($editor)->put("/teams/{$team->id}", [
            'name' => 'Editor Updated Name',
        ]);

        $response->assertForbidden();

        $team->refresh();
        $this->assertNotEquals('Editor Updated Name', $team->name);
    }

    public function test_non_owner_cannot_delete_team(): void
    {
        $owner = $this->createUserWithTeam();
        $admin = User::factory()->create();
        $team = $owner->currentTeam;
        $this->addUserToTeam($admin, $team, 'admin');

        $response = $this->actingAs($admin)->delete("/teams/{$team->id}");

        $response->assertForbidden();

        // Team should still exist
        $this->assertDatabaseHas('teams', ['id' => $team->id]);
    }

    public function test_owner_can_delete_team(): void
    {
        $user = $this->createUserWithTeam();

        // Create a second team (non-personal) for deletion
        $secondTeam = $this->createTeamWithMembers($user, [], [
            'name' => 'Deletable Team',
            'plan_type' => 'business',
        ]);
        // Add a second member so isPersonal() returns false if needed,
        // but plan_type 'business' already ensures it is not personal.

        $response = $this->actingAs($user)->delete("/teams/{$secondTeam->id}");

        $response->assertRedirect(route('teams.index'));
        $this->assertDatabaseMissing('teams', ['id' => $secondTeam->id]);
    }

    public function test_owner_cannot_delete_personal_team(): void
    {
        $user = $this->createUserWithTeam();
        $personalTeam = $user->currentTeam;

        // Personal team: plan_type=free, single member
        $response = $this->actingAs($user)->delete("/teams/{$personalTeam->id}");

        $response->assertRedirect();
        // Team should still exist
        $this->assertDatabaseHas('teams', ['id' => $personalTeam->id]);
    }

    public function test_user_can_switch_team(): void
    {
        $user = $this->createUserWithTeam();
        $personalTeam = $user->currentTeam;

        // Create a second team
        $secondTeam = $this->createTeamWithMembers($user, [], [
            'name' => 'Second Team',
        ]);

        $this->assertEquals($personalTeam->id, $user->current_team_id);

        $response = $this->actingAs($user)->put("/teams/{$secondTeam->id}/switch");

        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals($secondTeam->id, $user->current_team_id);
    }

    public function test_user_cannot_switch_to_team_they_dont_belong_to(): void
    {
        $user = $this->createUserWithTeam();
        $otherUser = $this->createUserWithTeam();
        $otherTeam = $otherUser->currentTeam;

        $response = $this->actingAs($user)->put("/teams/{$otherTeam->id}/switch");

        $response->assertForbidden();

        $user->refresh();
        $this->assertNotEquals($otherTeam->id, $user->current_team_id);
    }
}
