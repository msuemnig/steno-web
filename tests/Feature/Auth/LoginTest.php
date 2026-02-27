<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = $this->createUserWithTeam([
            'email' => 'jane@example.com',
            'password' => 'SecurePass123!',
        ]);

        $response = $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'SecurePass123!',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $this->createUserWithTeam([
            'email' => 'jane@example.com',
            'password' => 'SecurePass123!',
        ]);

        $response = $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'WrongPassword456!',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = $this->createUserWithTeam();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
