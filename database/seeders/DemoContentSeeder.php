<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\Board;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Pin;
use App\Models\PinLike;
use App\Models\Save;
use App\Models\User;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    /**
     * Seed demo users, boards, pins, saves and social interactions.
     */
    public function run(): void
    {
        /** @var User $demo */
        $demo = User::factory()->create([
            'name' => 'Demo User',
            'username' => 'demo',
            'email' => 'demo@pinspire.test',
            'password' => Hash::make('password'),
            'bio' => 'Akun demo Pinspire. Simpan ide, temukan cerita.',
            'website' => 'https://pinspire.test',
        ]);

        /** @var Collection<int, User> $creators */
        $creators = collect([
            [
                'name' => 'Nara Studio',
                'username' => 'nara.studio',
                'email' => 'nara@pinspire.test',
                'bio' => 'Ruang yang tenang, detail yang bermakna.',
            ],
            [
                'name' => 'Raka Pradana',
                'username' => 'raka.jalan',
                'email' => 'raka@pinspire.test',
                'bio' => 'Menemukan cerita di setiap perjalanan.',
            ],
            [
                'name' => 'Alya Putri',
                'username' => 'alya.collects',
                'email' => 'alya@pinspire.test',
                'bio' => 'Warna, tekstur, dan sedikit rasa ingin tahu.',
            ],
            [
                'name' => 'Mika Atelier',
                'username' => 'mika.atelier',
                'email' => 'mika@pinspire.test',
                'bio' => 'Ide sehari-hari yang layak disimpan.',
            ],
        ])->map(fn (array $data): User => User::factory()->create([
            ...$data,
            'password' => Hash::make('password'),
            'avatar' => $this->copyAvatar(),
        ]));

        /** @var Collection<int, User> $users */
        $users = collect([$demo])->concat($creators);

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

        foreach ($users as $user) {
            $targets = $users->where('id', '!=', $user->id)->random(2);

            foreach ($targets as $target) {
                Follow::firstOrCreate([
                    'follower_id' => $user->id,
                    'following_id' => $target->id,
                ]);
            }
        }

        foreach ($pins->random(30) as $pin) {
            $liker = $users->where('id', '!=', $pin->user_id)->random();

            PinLike::firstOrCreate([
                'user_id' => $liker->id,
                'pin_id' => $pin->id,
            ]);
        }

        foreach ($pins->random(18) as $pin) {
            $commenter = $users->where('id', '!=', $pin->user_id)->random();

            Comment::create([
                'pin_id' => $pin->id,
                'user_id' => $commenter->id,
                'body' => fake()->randomElement([
                    'Simpan untuk nanti. Warnanya pas banget.',
                    'Ide bagus, terima kasih sudah berbagi!',
                    'Sudut ini terasa tenang. Suka sekali.',
                    'Menyimpan ini untuk proyek akhir pekan.',
                    'Komposisinya rapi, inspirasinya dapat.',
                ]),
            ]);
        }

        $this->seedConversations($demo, $creators, $pins);
        $this->seedNotifications($demo, $creators, $pins);
    }

    /**
     * Create a pin with a real photo from the Flutter assets when available.
     */
    private function createPin(User $user): Pin
    {
        $categories = ['Interior', 'Arsitektur', 'Travel', 'Fashion', 'Kuliner', 'Alam', 'Kreatif'];
        $vibes = ['Calm', 'Warm', 'Bold', 'Fresh'];
        $tags = ['minimalis', 'rumah', 'inspirasi', 'warna', 'ruang', 'kreatif', 'alam', 'kota', 'hangat', 'tenang'];
        $palettes = [
            [0xFFDAC9B7, 0xFF8D806F, 0xFF42463C],
            [0xFFC4DCD7, 0xFF73AAA4, 0xFF2D5B66],
            [0xFFE3C8A8, 0xFFA97D54, 0xFF47372C],
            [0xFFE7CFC5, 0xFFAA7162, 0xFF362F35],
            [0xFFCCD3B6, 0xFF83906C, 0xFF3B5040],
            [0xFFE5C9BD, 0xFFB2786B, 0xFF485267],
            [0xFFC3D1D7, 0xFF8295A2, 0xFF3D5260],
        ];

        [$imagePath, $width, $height] = $this->placeholderImage();

        return $user->pins()->create([
            'title' => Str::title(fake()->words(random_int(2, 5), true)),
            'description' => fake()->boolean(80) ? fake()->sentence(random_int(6, 14)) : null,
            'category' => fake()->randomElement($categories),
            'vibe' => fake()->randomElement($vibes),
            'tags' => fake()->randomElements($tags, random_int(2, 4)),
            'palette' => fake()->randomElement($palettes),
            'image_path' => $imagePath,
            'source_url' => fake()->boolean(30) ? fake()->url() : null,
            'image_width' => $width,
            'image_height' => $height,
        ]);
    }

    /**
     * Copy a demo photo from the Flutter project or render a gradient PNG.
     *
     * @return array{0: string, 1: int, 2: int}
     */
    private function placeholderImage(): array
    {
        $assetDirectory = base_path('../pinspire/assets/images');
        $files = is_dir($assetDirectory)
            ? glob($assetDirectory.'/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: []
            : [];

        if ($files !== []) {
            $source = fake()->randomElement($files);
            $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
            $path = 'pins/demo-'.Str::random(12).'.'.$extension;
            $target = Storage::disk('public')->path($path);

            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0o775, true);
            }

            copy($source, $target);
            $size = @getimagesize($target);

            if ($size !== false) {
                return [$path, $size[0], $size[1]];
            }
        }

        $width = random_int(600, 1200);
        $height = (int) round($width * random_int(90, 160) / 100);
        $path = 'pins/demo-'.Str::random(12).'.png';

        $this->generatePlaceholderImage(Storage::disk('public')->path($path), $width, $height);

        return [$path, $width, $height];
    }

    /**
     * Copy a profile photo for a seeded creator.
     */
    private function copyAvatar(): ?string
    {
        $assetDirectory = base_path('../pinspire/assets/images');
        $files = is_dir($assetDirectory)
            ? glob($assetDirectory.'/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: []
            : [];

        if ($files === []) {
            return null;
        }

        $source = fake()->randomElement($files);
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $path = 'avatars/demo-'.Str::random(12).'.'.$extension;
        $target = Storage::disk('public')->path($path);

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0o775, true);
        }

        copy($source, $target);

        return $path;
    }

    /**
     * Seed a few conversations between the demo account and the creators.
     *
     * @param  Collection<int, User>  $creators
     * @param  Collection<int, Pin>  $pins
     */
    private function seedConversations(User $demo, Collection $creators, Collection $pins): void
    {
        $lines = [
            'Halo! Aku mengumpulkan referensi warna hangat. Kamu sedang bikin board apa?',
            'Selamat datang! Coba lihat koleksi sudut kerja yang baru.',
            'Pin barumu keren. Boleh simpan ke board inspirasiku?',
        ];

        foreach ($creators->take(3) as $index => $creator) {
            Message::create([
                'sender_id' => $creator->id,
                'recipient_id' => $demo->id,
                'body' => $lines[$index],
                'read_at' => $index === 1 ? now() : null,
            ]);

            if ($index === 0) {
                Message::create([
                    'sender_id' => $demo->id,
                    'recipient_id' => $creator->id,
                    'body' => 'Lagi mengumpulkan ide interior dan perjalanan. Terima kasih sudah menyapa!',
                    'read_at' => now(),
                ]);
            }
        }
    }

    /**
     * Seed a handful of notifications for the demo account.
     *
     * @param  Collection<int, User>  $creators
     * @param  Collection<int, Pin>  $pins
     */
    private function seedNotifications(User $demo, Collection $creators, Collection $pins): void
    {
        $demoPins = $pins->where('user_id', $demo->id)->values();

        AppNotification::create([
            'user_id' => $demo->id,
            'actor_id' => $creators[0]->id,
            'type' => 'follow',
            'title' => "{$creators[0]->name} mulai mengikuti kamu",
            'body' => $creators[0]->bio,
            'target_type' => 'user',
            'target_id' => $creators[0]->id,
            'image_path' => $creators[0]->avatar,
        ]);

        if ($demoPins->isNotEmpty()) {
            $pin = $demoPins->first();

            AppNotification::create([
                'user_id' => $demo->id,
                'actor_id' => $creators[1]->id,
                'type' => 'like',
                'title' => "{$creators[1]->name} menyukai pinmu",
                'body' => $pin->title,
                'target_type' => 'pin',
                'target_id' => $pin->id,
                'image_path' => $pin->image_path,
            ]);

            AppNotification::create([
                'user_id' => $demo->id,
                'actor_id' => $creators[2]->id,
                'type' => 'comment',
                'title' => "{$creators[2]->name} mengomentari pinmu",
                'body' => 'Simpan untuk nanti. Warnanya pas banget.',
                'target_type' => 'pin',
                'target_id' => $pin->id,
                'image_path' => $pin->image_path,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Render a simple gradient PNG without requiring the GD extension.
     */
    private function generatePlaceholderImage(string $path, int $width, int $height): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }

        $from = [random_int(40, 220), random_int(40, 220), random_int(40, 220)];
        $to = [random_int(40, 220), random_int(40, 220), random_int(40, 220)];
        $raw = '';

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / max($height - 1, 1);
            $row = chr((int) ($from[0] + ($to[0] - $from[0]) * $ratio))
                .chr((int) ($from[1] + ($to[1] - $from[1]) * $ratio))
                .chr((int) ($from[2] + ($to[2] - $from[2]) * $ratio));

            $raw .= "\x00".str_repeat($row, $width);
        }

        $png = "\x89PNG\r\n\x1a\n";
        $png .= $this->pngChunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0));
        $png .= $this->pngChunk('IDAT', gzcompress($raw, 6));
        $png .= $this->pngChunk('IEND', '');

        file_put_contents($path, $png);
    }

    /**
     * Build a single PNG chunk.
     */
    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
