<?php

namespace Tests\Feature\Api\V1;

use App\Models\Board;
use App\Models\Pin;
use App\Models\Save;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_save_a_pin_to_their_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id, 'cover_image_path' => null]);
        $pin = Pin::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/pins/{$pin->id}/save", ['board_id' => $board->id])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.board.id', $board->id)
            ->assertJsonPath('data.pin.id', $pin->id);

        $this->assertDatabaseHas('saves', [
            'user_id' => $user->id,
            'board_id' => $board->id,
            'pin_id' => $pin->id,
        ]);

        $this->assertSame($pin->image_path, $board->fresh()->cover_image_path);
    }

    public function test_saving_the_same_pin_twice_is_idempotent(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $pin = Pin::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/pins/{$pin->id}/save", ['board_id' => $board->id])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/pins/{$pin->id}/save", ['board_id' => $board->id])
            ->assertOk()
            ->assertJsonPath('message', 'Pin is already saved to this board.');

        $this->assertDatabaseCount('saves', 1);
    }

    public function test_a_user_cannot_save_a_pin_to_someone_elses_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create();
        $pin = Pin::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/pins/{$pin->id}/save", ['board_id' => $board->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['board_id']);

        $this->assertDatabaseCount('saves', 0);
    }

    public function test_a_user_can_unsave_a_pin(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $pin = Pin::factory()->create();

        Save::create(['user_id' => $user->id, 'board_id' => $board->id, 'pin_id' => $pin->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/pins/{$pin->id}/save", ['board_id' => $board->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('saves', 0);
    }

    public function test_unsaving_a_pin_that_is_not_saved_returns_a_404(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $pin = Pin::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/pins/{$pin->id}/save", ['board_id' => $board->id])
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_it_lists_the_pins_saved_by_the_user(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $pin = Pin::factory()->create(['title' => 'Saved by me']);

        Save::create(['user_id' => $user->id, 'board_id' => $board->id, 'pin_id' => $pin->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/saves')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.pin.title', 'Saved by me')
            ->assertJsonPath('data.0.pin.is_saved', true)
            ->assertJsonPath('data.0.board.id', $board->id);
    }

    public function test_saving_a_pin_requires_authentication(): void
    {
        $pin = Pin::factory()->create();
        $board = Board::factory()->create();

        $this->postJson("/api/v1/pins/{$pin->id}/save", ['board_id' => $board->id])
            ->assertStatus(401);
    }
}
