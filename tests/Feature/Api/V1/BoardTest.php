<?php

namespace Tests\Feature\Api\V1;

use App\Models\Board;
use App\Models\Pin;
use App\Models\Save;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_authenticated_users_boards(): void
    {
        $user = User::factory()->create();
        Board::factory()->count(2)->create(['user_id' => $user->id]);
        Board::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/boards')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_a_user_can_create_a_board(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/boards', [
                'name' => 'Travel Ideas',
                'description' => 'Places to visit',
                'is_private' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Travel Ideas')
            ->assertJsonPath('data.slug', 'travel-ideas')
            ->assertJsonPath('data.is_private', true)
            ->assertJsonPath('data.pins_count', 0);

        $this->assertDatabaseHas('boards', ['user_id' => $user->id, 'slug' => 'travel-ideas']);
    }

    public function test_board_slugs_are_unique_per_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/boards', ['name' => 'Travel'])->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/boards', ['name' => 'Travel'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'travel-2');
    }

    public function test_a_user_can_update_their_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id, 'name' => 'Old', 'slug' => 'old']);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/boards/{$board->id}", ['name' => 'New Name', 'is_private' => true])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.slug', 'new-name')
            ->assertJsonPath('data.is_private', true);
    }

    public function test_a_user_cannot_update_someone_elses_board(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->putJson("/api/v1/boards/{$board->id}", ['name' => 'Hijacked'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_a_user_can_delete_their_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/boards/{$board->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    }

    public function test_a_private_board_is_hidden_from_other_users(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $board = Board::factory()->private()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/boards/{$board->id}")
            ->assertStatus(403);

        $this->getJson("/api/v1/boards/{$board->id}")->assertStatus(403);
    }

    public function test_the_owner_can_view_a_private_board(): void
    {
        $owner = User::factory()->create();
        $board = Board::factory()->private()->create(['user_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/boards/{$board->id}")
            ->assertOk()
            ->assertJsonPath('data.is_private', true);
    }

    public function test_a_board_lists_the_pins_saved_to_it(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $pin = Pin::factory()->create(['title' => 'Saved pin']);

        Save::create([
            'user_id' => $user->id,
            'board_id' => $board->id,
            'pin_id' => $pin->id,
        ]);

        $this->getJson("/api/v1/boards/{$board->id}/pins")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pin->id)
            ->assertJsonPath('data.0.title', 'Saved pin');
    }

    public function test_creating_a_board_requires_a_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/boards', ['description' => 'No name'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
