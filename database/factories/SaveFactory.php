<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\Pin;
use App\Models\Save;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Save>
 */
class SaveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'board_id' => Board::factory(),
            'pin_id' => Pin::factory(),
        ];
    }
}
