<?php

namespace Tests\Feature\Api\V1;

use App\Models\Pin;
use App\Models\Save;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PinTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public', ['url' => 'http://localhost/storage']);
    }

    public function test_the_feed_lists_pins_with_their_author(): void
    {
        Pin::factory()->count(3)->create();

        $this->getJson('/api/v1/pins')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    ['id', 'title', 'description', 'image_url', 'saves_count', 'is_saved', 'user' => ['id', 'name', 'username']],
                ],
                'meta' => ['current_page', 'per_page', 'last_page', 'total', 'has_more_pages'],
            ]);
    }

    public function test_the_feed_marks_pins_saved_by_the_viewer(): void
    {
        $user = User::factory()->create();
        $board = $user->boards()->create(['name' => 'Saved', 'slug' => 'saved']);
        $pin = Pin::factory()->create();

        Save::create([
            'user_id' => $user->id,
            'board_id' => $board->id,
            'pin_id' => $pin->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/pins')
            ->assertOk()
            ->assertJsonPath('data.0.is_saved', true)
            ->assertJsonPath('data.0.saves_count', 1);
    }

    public function test_a_user_can_upload_a_pin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->post('/api/v1/pins', [
            'title' => 'Sunset',
            'description' => 'A nice sunset',
            'source_url' => 'https://example.com/sunset',
            'image' => $this->fakePng('sunset.png', 800, 600),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Sunset')
            ->assertJsonPath('data.image_width', 800)
            ->assertJsonPath('data.image_height', 600)
            ->assertJsonPath('data.user.id', $user->id);

        $pin = Pin::query()->firstOrFail();

        $this->assertSame($user->id, $pin->user_id);
        Storage::disk('public')->assertExists($pin->image_path);
    }

    public function test_uploading_a_pin_requires_an_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->post('/api/v1/pins', ['title' => 'No image'], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_a_pin_shows_its_detail(): void
    {
        $pin = Pin::factory()->create(['title' => 'Detail pin']);

        $this->getJson("/api/v1/pins/{$pin->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $pin->id)
            ->assertJsonPath('data.title', 'Detail pin');
    }

    public function test_a_missing_pin_returns_a_json_404(): void
    {
        $this->getJson('/api/v1/pins/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The requested resource was not found.');
    }

    public function test_a_user_can_update_their_own_pin(): void
    {
        $user = User::factory()->create();
        $pin = Pin::factory()->create(['user_id' => $user->id, 'title' => 'Old title']);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/pins/{$pin->id}", ['title' => 'New title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'New title');

        $this->assertDatabaseHas('pins', ['id' => $pin->id, 'title' => 'New title']);
    }

    public function test_a_user_cannot_update_someone_elses_pin(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $pin = Pin::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->putJson("/api/v1/pins/{$pin->id}", ['title' => 'Hacked'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_a_user_can_delete_their_own_pin(): void
    {
        $user = User::factory()->create();
        $pin = Pin::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/pins/{$pin->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('pins', ['id' => $pin->id]);
    }

    public function test_a_user_cannot_delete_someone_elses_pin(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $pin = Pin::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/v1/pins/{$pin->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('pins', ['id' => $pin->id]);
    }
}
