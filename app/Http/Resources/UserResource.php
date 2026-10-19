<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Create a new resource instance.
     */
    public function __construct(mixed $resource, private readonly bool $includeEmail = false)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->when(
                $this->includeEmail || $request->user()?->is($this->resource) === true,
                $this->email,
            ),
            'avatar_url' => $this->avatar ? Storage::disk('public')->url($this->avatar) : null,
            'bio' => $this->bio,
            'website' => $this->website,
            'pins_count' => (int) ($this->pins_count ?? 0),
            'boards_count' => (int) ($this->boards_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
