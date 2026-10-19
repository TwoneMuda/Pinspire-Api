<?php

namespace App\Http\Resources;

use App\Models\Pin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Pin
 */
class PinResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => Storage::disk('public')->url($this->image_path),
            'image_width' => $this->image_width,
            'image_height' => $this->image_height,
            'source_url' => $this->source_url,
            'saves_count' => (int) ($this->saves_count ?? 0),
            'is_saved' => (bool) ($this->is_saved_by_me ?? false),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
