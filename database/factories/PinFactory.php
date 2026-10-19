<?php

namespace Database\Factories;

use App\Models\Pin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pin>
 */
class PinFactory extends Factory
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
            'title' => Str::title(fake()->words(4, true)),
            'description' => fake()->boolean(70) ? fake()->paragraph() : null,
            'image_path' => 'pins/'.fake()->uuid().'.jpg',
            'source_url' => fake()->boolean(30) ? fake()->url() : null,
            'image_width' => fake()->randomElement([600, 800, 1000, 1200]),
            'image_height' => fake()->randomElement([600, 800, 1200, 1500]),
        ];
    }
}
