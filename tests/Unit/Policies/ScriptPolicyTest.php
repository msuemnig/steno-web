<?php

namespace Tests\Unit\Policies;

use App\Models\Script;
use App\Models\Team;
use App\Models\User;
use App\Policies\ScriptPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScriptPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ScriptPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ScriptPolicy();
    }

    /**
     * Create a team with an owner and attach the owner as a member.
     */
    private function createTeamWithOwner(): array
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($owner, ['role' => 'owner']);

        return [$team, $owner];
    }

    /**
     * Add a user to a team with a given role.
     */
    private function addMember(Team $team, string $role): User
    {
        $user = User::factory()->create();
        $team->members()->attach($user, ['role' => $role]);

        return $user;
    }

    /**
     * Create a script belonging to a team and user.
     */
    private function createScript(Team $team, User $user): Script
    {
        return Script::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);
    }

    // ---------------------------------------------------------------
    // view
    // ---------------------------------------------------------------

    public function test_owner_can_view_team_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $script = $this->createScript($team, $owner);

        $this->assertTrue($this->policy->view($owner, $script));
    }

    public function test_admin_can_view_team_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $admin = $this->addMember($team, 'admin');
        $script = $this->createScript($team, $owner);

        $this->assertTrue($this->policy->view($admin, $script));
    }

    public function test_editor_can_view_team_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $script = $this->createScript($team, $owner);

        $this->assertTrue($this->policy->view($editor, $script));
    }

    public function test_viewer_can_view_team_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $script = $this->createScript($team, $owner);

        $this->assertTrue($this->policy->view($viewer, $script));
    }

    public function test_non_member_cannot_view_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $script = $this->createScript($team, $owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->policy->view($outsider, $script));
    }

    // ---------------------------------------------------------------
    // update
    // ---------------------------------------------------------------

    public function test_owner_can_update_any_team_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $script = $this->createScript($team, $editor);

        $this->assertTrue($this->policy->update($owner, $script));
    }

    public function test_admin_can_update_any_team_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $admin = $this->addMember($team, 'admin');
        $script = $this->createScript($team, $owner);

        $this->assertTrue($this->policy->update($admin, $script));
    }

    public function test_editor_can_update_own_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $script = $this->createScript($team, $editor);

        $this->assertTrue($this->policy->update($editor, $script));
    }

    public function test_editor_cannot_update_others_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $script = $this->createScript($team, $owner);

        $this->assertFalse($this->policy->update($editor, $script));
    }

    public function test_viewer_cannot_update_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $script = $this->createScript($team, $owner);

        $this->assertFalse($this->policy->update($viewer, $script));
    }

    public function test_non_member_cannot_update_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $script = $this->createScript($team, $owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->policy->update($outsider, $script));
    }

    // ---------------------------------------------------------------
    // delete
    // ---------------------------------------------------------------

    public function test_owner_can_delete_any_team_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $script = $this->createScript($team, $editor);

        $this->assertTrue($this->policy->delete($owner, $script));
    }

    public function test_admin_can_delete_any_team_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $admin = $this->addMember($team, 'admin');
        $script = $this->createScript($team, $owner);

        $this->assertTrue($this->policy->delete($admin, $script));
    }

    public function test_editor_can_delete_own_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $script = $this->createScript($team, $editor);

        $this->assertTrue($this->policy->delete($editor, $script));
    }

    public function test_editor_cannot_delete_others_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $script = $this->createScript($team, $owner);

        $this->assertFalse($this->policy->delete($editor, $script));
    }

    public function test_viewer_cannot_delete_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $script = $this->createScript($team, $owner);

        $this->assertFalse($this->policy->delete($viewer, $script));
    }

    public function test_non_member_cannot_delete_script(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $script = $this->createScript($team, $owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->policy->delete($outsider, $script));
    }

    // ---------------------------------------------------------------
    // viewAny / create (open to all authenticated users)
    // ---------------------------------------------------------------

    public function test_any_user_can_view_any_scripts(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_any_user_can_create_script(): void
    {
        $user = User::factory()->create();
        $this->assertTrue($this->policy->create($user));
    }
}
