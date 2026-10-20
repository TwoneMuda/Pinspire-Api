<?php

namespace Tests\Feature\Api\V1;

use App\Models\Board;
use App\Models\Pin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public', ['url' => 'http://localhost/storage']);
    }

    public function test_it_shows_a_public_profile_without_the_email(): void
    {
        User::factory()->create(['username' => 'janedoe', 'email' => 'jane@example.com']);

        $this->getJson('/api/v1/users/janedoe')
            ->assertOk()
            ->assertJsonPath('data.username', 'janedoe')
            ->assertJsonMissingPath('data.email');
    }

    public function test_the_owner_sees_their_own_email(): void
    {
        $user = User::factory()->create(['username' => 'janedoe']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users/janedoe')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_a_user_can_update_their_own_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'name' => 'New Name',
                'username' => 'newusername',
                'bio' => 'Hello there',
                'website' => 'https://example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.username', 'newusername')
            ->assertJsonPath('data.bio', 'Hello there');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'newusername']);
    }

    public function test_updating_a_username_requires_uniqueness(): void
    {
        User::factory()->create(['username' => 'taken']);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', ['username' => 'taken'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_a_user_can_upload_an_avatar(): void
    {
        $user = User::factory()->create(['avatar' => null]);

        $response = $this->actingAs($user, 'sanctum')->post('/api/v1/profile/avatar', [
            'avatar' => $this->fakePng('avatar.png', 300, 300),
        ], ['Accept' => 'application/json']);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertNotNull($response->json('data.avatar_url'));
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_it_lists_the_pins_uploaded_by_a_user(): void
    {
        $user = User::factory()->create(['username' => 'janedoe']);
        Pin::factory()->count(2)->create(['user_id' => $user->id]);
        Pin::factory()->create();

        $this->getJson('/api/v1/users/janedoe/pins')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_hides_private_boards_from_other_users(): void
    {
        $owner = User::factory()->create(['username' => 'janedoe']);
        Board::factory()->private()->create(['user_id' => $owner->id]);
        Board::factory()->create(['user_id' => $owner->id]);

        $this->getJson('/api/v1/users/janedoe/boards')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_missing_user_returns_a_json_404(): void
    {
        $this->getJson('/api/v1/users/does-not-exist')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
