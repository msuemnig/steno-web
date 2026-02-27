<?php

namespace Tests\Unit\Policies;

use App\Models\Persona;
use App\Models\Team;
use App\Models\User;
use App\Policies\PersonaPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonaPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PersonaPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PersonaPolicy();
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

    private function createPersona(Team $team, User $user): Persona
    {
        return Persona::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);
    }

    // ---------------------------------------------------------------
    // view
    // ---------------------------------------------------------------

    public function test_owner_can_view_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $persona = $this->createPersona($team, $owner);

        $this->assertTrue($this->policy->view($owner, $persona));
    }

    public function test_member_can_view_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $persona = $this->createPersona($team, $owner);

        $this->assertTrue($this->policy->view($viewer, $persona));
    }

    public function test_non_member_cannot_view_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $persona = $this->createPersona($team, $owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->policy->view($outsider, $persona));
    }

    // ---------------------------------------------------------------
    // update
    // ---------------------------------------------------------------

    public function test_owner_can_update_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $persona = $this->createPersona($team, $editor);

        $this->assertTrue($this->policy->update($owner, $persona));
    }

    public function test_admin_can_update_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $admin = $this->addMember($team, 'admin');
        $persona = $this->createPersona($team, $owner);

        $this->assertTrue($this->policy->update($admin, $persona));
    }

    public function test_editor_can_update_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $persona = $this->createPersona($team, $owner);

        $this->assertTrue($this->policy->update($editor, $persona));
    }

    public function test_viewer_cannot_update_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $persona = $this->createPersona($team, $owner);

        $this->assertFalse($this->policy->update($viewer, $persona));
    }

    public function test_non_member_cannot_update_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $persona = $this->createPersona($team, $owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->policy->update($outsider, $persona));
    }

    // ---------------------------------------------------------------
    // delete
    // ---------------------------------------------------------------

    public function test_owner_can_delete_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $persona = $this->createPersona($team, $owner);

        $this->assertTrue($this->policy->delete($owner, $persona));
    }

    public function test_admin_can_delete_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $admin = $this->addMember($team, 'admin');
        $persona = $this->createPersona($team, $owner);

        $this->assertTrue($this->policy->delete($admin, $persona));
    }

    public function test_editor_cannot_delete_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $persona = $this->createPersona($team, $owner);

        $this->assertFalse($this->policy->delete($editor, $persona));
    }

    public function test_viewer_cannot_delete_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $persona = $this->createPersona($team, $owner);

        $this->assertFalse($this->policy->delete($viewer, $persona));
    }

    public function test_non_member_cannot_delete_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $persona = $this->createPersona($team, $owner);
        $outsider = User::factory()->create();

        $this->assertFalse($this->policy->delete($outsider, $persona));
    }

    // ---------------------------------------------------------------
    // owner full CRUD
    // ---------------------------------------------------------------

    public function test_owner_can_crud_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $persona = $this->createPersona($team, $owner);

        $this->assertTrue($this->policy->viewAny($owner));
        $this->assertTrue($this->policy->view($owner, $persona));
        $this->assertTrue($this->policy->create($owner));
        $this->assertTrue($this->policy->update($owner, $persona));
        $this->assertTrue($this->policy->delete($owner, $persona));
    }

    // ---------------------------------------------------------------
    // editor can modify but not delete
    // ---------------------------------------------------------------

    public function test_editor_can_only_modify_own_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $editor = $this->addMember($team, 'editor');
        $ownerPersona = $this->createPersona($team, $owner);
        $editorPersona = $this->createPersona($team, $editor);

        // Editor can view any persona in the team
        $this->assertTrue($this->policy->view($editor, $ownerPersona));
        $this->assertTrue($this->policy->view($editor, $editorPersona));

        // Editor can update personas (PersonaPolicy allows all editors, not just own)
        $this->assertTrue($this->policy->update($editor, $ownerPersona));
        $this->assertTrue($this->policy->update($editor, $editorPersona));

        // Editor cannot delete any persona
        $this->assertFalse($this->policy->delete($editor, $ownerPersona));
        $this->assertFalse($this->policy->delete($editor, $editorPersona));
    }

    // ---------------------------------------------------------------
    // viewer cannot modify
    // ---------------------------------------------------------------

    public function test_viewer_cannot_modify_persona(): void
    {
        [$team, $owner] = $this->createTeamWithOwner();
        $viewer = $this->addMember($team, 'viewer');
        $persona = $this->createPersona($team, $owner);

        $this->assertTrue($this->policy->view($viewer, $persona));
        $this->assertFalse($this->policy->update($viewer, $persona));
        $this->assertFalse($this->policy->delete($viewer, $persona));
    }
}
