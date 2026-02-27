<?php

namespace Tests\Feature\Auth;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->current_team_id);
    }

    public function test_registration_requires_valid_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'not-an-email',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', [
            'name' => 'Jane Doe',
        ]);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'DifferentPass456!',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', [
            'email' => 'jane@example.com',
        ]);
    }

    public function test_registration_creates_personal_team(): void
    {
        $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);

        // User should have exactly one team
        $this->assertCount(1, $user->teams);

        // The team should be owned by the user
        $team = $user->teams->first();
        $this->assertEquals($user->id, $team->owner_id);

        // The user's role on the team should be 'owner'
        $this->assertEquals('owner', $team->pivot->role);

        // The team should be the user's current team
        $this->assertEquals($team->id, $user->current_team_id);

        // The team name should follow the convention
        $this->assertEquals("Jane Doe's Team", $team->name);

        // The team should be on the free plan
        $this->assertEquals('free', $team->plan_type);
    }
}
