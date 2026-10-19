<?php

namespace App\Http\Resources;

use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Board
 */
class BoardResource extends JsonResource
{
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
            'slug' => $this->slug,
            'description' => $this->description,
            'is_private' => (bool) $this->is_private,
            'cover_image_url' => $this->cover_image_path
                ? Storage::disk('public')->url($this->cover_image_path)
                : null,
            'pins_count' => (int) ($this->pins_count ?? 0),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
