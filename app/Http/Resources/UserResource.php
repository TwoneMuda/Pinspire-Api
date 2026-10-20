<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'avatar_url' => Media::url($this->avatar),
            'bio' => $this->bio,
            'website' => $this->website,
            'pins_count' => (int) ($this->pins_count ?? 0),
            'boards_count' => (int) ($this->boards_count ?? 0),
            'followers_count' => (int) ($this->followers_count ?? 0),
            'following_count' => (int) ($this->following_count ?? 0),
            'is_following' => (bool) ($this->is_following ?? false),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
