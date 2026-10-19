<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'flutter',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'janedoe')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'username', 'email', 'avatar_url', 'pins_count', 'boards_count'],
                    'access_token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'username' => 'janedoe']);
        $this->assertNotEmpty($response->json('data.access_token'));
    }

    public function test_registration_requires_unique_credentials(): void
    {
        User::factory()->create(['email' => 'taken@example.com', 'username' => 'taken']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'username' => 'taken',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email', 'username']);
    }

    public function test_registration_requires_a_confirmed_password(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'something-else',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_a_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'jane@example.com');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_an_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_a_user_can_logout_and_the_token_is_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guests_receive_a_json_401_response(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated. Please log in to continue.');
    }
}
