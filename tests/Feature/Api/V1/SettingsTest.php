<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_the_account_settings_with_default_preferences(): void
    {
        $user = User::factory()->create(['preferences' => null]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.preferences.email_notifications', true)
            ->assertJsonPath('data.preferences.push_notifications', true);
    }

    public function test_a_user_can_update_their_email_and_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/settings', [
                'email' => 'new@example.com',
                'preferences' => ['push_notifications' => false],
            ])
            ->assertOk()
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonPath('data.preferences.push_notifications', false)
            ->assertJsonPath('data.preferences.email_notifications', true);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new@example.com']);
    }

    public function test_updating_the_email_requires_uniqueness(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/settings', ['email' => 'taken@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_a_user_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/settings/password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_changing_the_password_requires_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/settings/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/settings/password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'different',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
