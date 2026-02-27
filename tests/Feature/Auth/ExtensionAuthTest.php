<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ExtensionAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_extension_login_page(): void
    {
        $user = $this->createUserWithTeam();

        $response = $this->actingAs($user)->get('/auth/extension-login');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/ExtensionLogin')
            ->has('token')
            ->has('user')
            ->where('user.id', $user->id)
            ->where('user.name', $user->name)
            ->where('user.email', $user->email)
        );
    }

    public function test_authenticated_user_can_generate_extension_token(): void
    {
        $user = $this->createUserWithTeam();

        $response = $this->actingAs($user)->post('/auth/extension-token');

        $response->assertOk();
        $response->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email'],
        ]);
        $response->assertJson([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);

        // Verify the token string is not empty
        $data = $response->json();
        $this->assertNotEmpty($data['token']);
    }

    public function test_unauthenticated_user_cannot_access_extension_login(): void
    {
        $response = $this->get('/auth/extension-login');

        $response->assertRedirect('/login');
    }

    public function test_extension_token_is_valid_sanctum_token(): void
    {
        $user = $this->createUserWithTeam();

        // Generate a token via the extension-token endpoint
        $response = $this->actingAs($user)->post('/auth/extension-token');
        $response->assertOk();

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // Use the token to access the /api/user endpoint (Sanctum-protected)
        $apiResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('/api/user');

        $apiResponse->assertOk();
        $apiResponse->assertJson([
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }
}
