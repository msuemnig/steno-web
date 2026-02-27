<?php

namespace Tests\Unit\Policies;

use App\Models\Site;
use App\Models\Team;
use App\Models\User;
use App\Policies\SitePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePolicyTest extends TestCase
{
    use RefreshDatabase;

    private SitePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SitePolicy();
    }

    private function createTeamWithOwner(): array
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->attach($owner, ['role' => 'owner']);

        return [$team, $owner];
    }

    private function addMember(Team $team, string $role): User
    {
        $user = User::factory()->create();
        $team->members()->attach($user, ['role' => $role]);

        return $user;
    }

    private function createSite(Team $team, User $user): Site
    {
        return Site::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);
    }

    // ---------------------------------------------------------------
    // view
    // ---------------------------------------------------------------

    public function test_owner_can_view_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $site = $this->createSite($team, $owner);

        $this->assertTrue($this->policy->view($owner, $site));
    }

    public function test_member_can_view_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $site = $this->createSite($team, $owner);

        $this->assertTrue($this->policy->view($viewer, $site));
    }

    public function test_non_member_cannot_view_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $site = $this->createSite($team, $owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->policy->view($outsider, $site));
    }

    // ---------------------------------------------------------------
    // update
    // ---------------------------------------------------------------

    public function test_owner_can_update_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $site = $this->createSite($team, $editor);

        $this->assertTrue($this->policy->update($owner, $site));
    }

    public function test_admin_can_update_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $admin = $this->addMember($team, 'admin');
        $site = $this->createSite($team, $owner);

        $this->assertTrue($this->policy->update($admin, $site));
    }

    public function test_editor_can_update_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $site = $this->createSite($team, $owner);

        $this->assertTrue($this->policy->update($editor, $site));
    }

    public function test_viewer_cannot_update_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $site = $this->createSite($team, $owner);

        $this->assertFalse($this->policy->update($viewer, $site));
    }

    public function test_non_member_cannot_update_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $site = $this->createSite($team, $owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->policy->update($outsider, $site));
    }

    // ---------------------------------------------------------------
    // delete
    // ---------------------------------------------------------------

    public function test_owner_can_delete_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $site = $this->createSite($team, $owner);

        $this->assertTrue($this->policy->delete($owner, $site));
    }

    public function test_admin_can_delete_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $admin = $this->addMember($team, 'admin');
        $site = $this->createSite($team, $owner);

        $this->assertTrue($this->policy->delete($admin, $site));
    }

    public function test_editor_cannot_delete_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $site = $this->createSite($team, $owner);

        $this->assertFalse($this->policy->delete($editor, $site));
    }

    public function test_viewer_cannot_delete_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $site = $this->createSite($team, $owner);

        $this->assertFalse($this->policy->delete($viewer, $site));
    }

    public function test_non_member_cannot_delete_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $site = $this->createSite($team, $owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->policy->delete($outsider, $site));
    }

    // ---------------------------------------------------------------
    // owner can do full CRUD
    // ---------------------------------------------------------------

    public function test_owner_can_crud_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $site = $this->createSite($team, $owner);

        $this->assertTrue($this->policy->viewAny($owner));
        $this->assertTrue($this->policy->view($owner, $site));
        $this->assertTrue($this->policy->create($owner));
        $this->assertTrue($this->policy->update($owner, $site));
        $this->assertTrue($this->policy->delete($owner, $site));
    }

    // ---------------------------------------------------------------
    // editor can modify but not delete
    // ---------------------------------------------------------------

    public function test_editor_can_only_modify_own_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $ownerSite = $this->createSite($team, $owner);
        $editorSite = $this->createSite($team, $editor);

        // Editor can view any site in the team
        $this->assertTrue($this->policy->view($editor, $ownerSite));
        $this->assertTrue($this->policy->view($editor, $editorSite));

        // Editor can update sites (SitePolicy allows all editors, not just own)
        $this->assertTrue($this->policy->update($editor, $ownerSite));
        $this->assertTrue($this->policy->update($editor, $editorSite));

        // Editor cannot delete any site
        $this->assertFalse($this->policy->delete($editor, $ownerSite));
        $this->assertFalse($this->policy->delete($editor, $editorSite));
    }

    // ---------------------------------------------------------------
    // viewer cannot modify
    // ---------------------------------------------------------------

    public function test_viewer_cannot_modify_site(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $site = $this->createSite($team, $owner);

        $this->assertTrue($this->policy->view($viewer, $site));
        $this->assertFalse($this->policy->update($viewer, $site));
        $this->assertFalse($this->policy->delete($viewer, $site));
    }
}
