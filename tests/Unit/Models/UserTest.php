<?php

namespace Tests\Unit\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private function createUserAndTeam(string $role = 'owner'): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $team->members()->attach($user, ['role' => $role]);

        return [$user, $team];
    }

    public function test_user_has_teams_relationship(): void
    {
        [$user, $team] = $this->createUserAndTeam();

        $this->assertCount(1, $user->teams);
        $this->assertTrue($user->teams->contains($team));
    }

    public function test_user_has_owned_teams_relationship(): void
    {
        [$user, $team] = $this->createUserAndTeam();

        $this->assertCount(1, $user->ownedTeams);
        $this->assertTrue($user->ownedTeams->contains($team));
    }

    public function test_user_has_current_team(): void
    {
        [$user, $team] = $this->createUserAndTeam();
        $user->update(['current_team_id' => $team->id]);
        $user->refresh();

        $this->assertNotNull($user->currentTeam);
        $this->assertEquals($team->id, $user->currentTeam->id);
    }

    public function test_user_can_switch_team(): void
    {
        [$user, $team1] = $this->createUserAndTeam();
        $team2 = Team::factory()->create(['owner_id' => $user->id]);
        $team2->members()->attach($user, ['role' => 'owner']);

        $user->switchTeam($team2);
        $user->refresh();

        $this->assertEquals($team2->id, $user->current_team_id);
    }

    public function test_user_has_personal_team(): void
    {
        [$user, $team] = $this->createUserAndTeam('owner');

        $personalTeam = $user->personalTeam();

        $this->assertNotNull($personalTeam);
        $this->assertEquals($team->id, $personalTeam->id);
        $this->assertEquals('free', $personalTeam->plan_type);
    }

    public function test_personal_team_returns_null_for_business_plan(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create([
            'owner_id' => $user->id,
            'plan_type' => 'business',
        ]);
        $team->members()->attach($user, ['role' => 'owner']);

        $this->assertNull($user->personalTeam());
    }

    public function test_role_on_returns_correct_role(): void
    {
        [$user, $team] = $this->createUserAndTeam('admin');

        $this->assertEquals('admin', $user->roleOn($team));
    }

    public function test_role_on_returns_null_for_non_member(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $this->assertNull($user->roleOn($team));
    }
}
