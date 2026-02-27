<?php

namespace Tests;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user with a personal team (mimics CreateNewUser action).
     */
    protected function createUserWithTeam(array $userAttributes = []): User
    {
        $user = User::factory()->create($userAttributes);

        $team = Team::factory()->create([
            'owner_id' => $user->id,
            'name' => $user->name . "'s Team",
            'plan_type' => 'free',
        ]);

        $team->members()->attach($user->id, ['role' => 'owner']);
        $user->update(['current_team_id' => $team->id]);

        return $user->fresh();
    }

    /**
     * Create a team with the given owner and optionally attach additional members.
     */
    protected function createTeamWithMembers(User $owner, array $members = [], array $teamAttributes = []): Team
    {
        $team = Team::factory()->create(array_merge([
            'owner_id' => $owner->id,
            'name' => 'Test Team',
        ], $teamAttributes));

        $team->members()->attach($owner->id, ['role' => 'owner']);

        foreach ($members as $member) {
            $user = $member['user'] ?? User::factory()->create();
            $role = $member['role'] ?? 'editor';
            $team->members()->attach($user->id, ['role' => $role]);
        }

        return $team->fresh();
    }

    /**
     * Add a user to a team with a given role.
     */
    protected function addUserToTeam(User $user, Team $team, string $role = 'editor'): void
    {
        $team->members()->attach($user->id, ['role' => $role]);
    }
}
