<?php

namespace Tests\Feature\Api\V1;

use App\Models\Pin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_searches_pins_by_title(): void
    {
        Pin::factory()->create(['title' => 'Blue Mountain Sunset']);
        Pin::factory()->create(['title' => 'City lights at night']);

        $this->getJson('/api/v1/search?q=Sunset')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Blue Mountain Sunset')
            ->assertJsonPath('meta.query', 'Sunset');
    }

    public function test_it_searches_pins_by_description(): void
    {
        Pin::factory()->create(['title' => 'Recipe', 'description' => 'A lovely chocolate cake']);
        Pin::factory()->create(['title' => 'Recipe', 'description' => 'Fresh green salad']);

        $this->getJson('/api/v1/search?q=chocolate')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'A lovely chocolate cake');
    }

    public function test_it_returns_an_empty_list_when_nothing_matches(): void
    {
        Pin::factory()->count(3)->create(['title' => 'Something else']);

        $this->getJson('/api/v1/search?q=zzzzzzz')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_it_marks_search_results_saved_by_the_viewer(): void
    {
        $user = User::factory()->create();
        $board = $user->boards()->create(['name' => 'Saved', 'slug' => 'saved']);
        $pin = Pin::factory()->create(['title' => 'Unique searchable title']);

        $board->pins()->attach($pin->id, ['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/search?q=searchable')
            ->assertOk()
            ->assertJsonPath('data.0.is_saved', true);
    }

    public function test_search_requires_a_query(): void
    {
        $this->getJson('/api/v1/search')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }
}
