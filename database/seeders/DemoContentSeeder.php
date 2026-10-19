<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Pin;
use App\Models\Save;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    /**
     * Seed demo users, boards, pins and saves, including generated placeholder images.
     */
    public function run(): void
    {
        /** @var Collection<int, User> $users */
        $users = User::factory()->count(4)->create()->prepend(
            User::factory()->create([
                'name' => 'Demo User',
                'username' => 'demo',
                'email' => 'demo@pinspire.test',
                'password' => Hash::make('password'),
                'bio' => 'Demo account for the Pinspire API.',
                'website' => 'https://pinspire.test',
            ]),
        );

        /** @var Collection<int, Board> $boards */
        $boards = $users->flatMap(fn (User $user): Collection => Board::factory()
            ->count(random_int(2, 3))
            ->create(['user_id' => $user->id]));

        /** @var Collection<int, Pin> $pins */
        $pins = $users->flatMap(fn (User $user): Collection => collect(range(1, 8))
            ->map(fn (): Pin => $this->createPin($user)));

        foreach ($boards as $board) {
            $selection = $pins->random(random_int(2, 5));

            foreach ($selection as $pin) {
                Save::firstOrCreate(
                    ['board_id' => $board->id, 'pin_id' => $pin->id],
                    ['user_id' => $board->user_id],
                );
            }

            $board->update(['cover_image_path' => $selection->first()->image_path]);
        }
    }

    /**
     * Create a pin with a generated placeholder image.
     */
    private function createPin(User $user): Pin
    {
        $width = random_int(600, 1200);
        $height = (int) round($width * random_int(90, 160) / 100);

        $imagePath = 'pins/demo-'.Str::random(12).'.jpg';
        $this->generatePlaceholderImage(Storage::disk('public')->path($imagePath), $width, $height);

        return $user->pins()->create([
            'title' => Str::title(fake()->words(random_int(2, 5), true)),
            'description' => fake()->boolean(70) ? fake()->sentence(random_int(6, 14)) : null,
            'image_path' => $imagePath,
            'source_url' => fake()->boolean(30) ? fake()->url() : null,
            'image_width' => $width,
            'image_height' => $height,
        ]);
    }

    /**
     * Render a simple gradient placeholder image so the feed shows real files.
     */
    private function generatePlaceholderImage(string $path, int $width, int $height): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }

        $image = imagecreatetruecolor($width, $height);

        $from = [random_int(40, 220), random_int(40, 220), random_int(40, 220)];
        $to = [random_int(40, 220), random_int(40, 220), random_int(40, 220)];

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / max($height - 1, 1);

            $color = imagecolorallocate(
                $image,
                (int) ($from[0] + ($to[0] - $from[0]) * $ratio),
                (int) ($from[1] + ($to[1] - $from[1]) * $ratio),
                (int) ($from[2] + ($to[2] - $from[2]) * $ratio),
            );

            imageline($image, 0, $y, $width, $y, $color);
        }

        $label = "Pinspire {$width}x{$height}";
        $white = imagecolorallocate($image, 255, 255, 255);

        imagestring(
            $image,
            4,
            (int) (($width - imagefontwidth(4) * strlen($label)) / 2),
            (int) ($height / 2),
            $label,
            $white,
        );

        imagejpeg($image, $path, 85);
        imagedestroy($image);
    }
}
