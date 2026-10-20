<?php

namespace App\Http\Resources;

use App\Models\Pin;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        $imageUrl = $this->image_path
            ? Media::url($this->image_path)
            : $this->image_url;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'vibe' => $this->vibe,
            'tags' => $this->tags ?? [],
            'palette' => $this->palette ?? [],
            'image_url' => $imageUrl,
            'image_width' => $this->image_width,
            'image_height' => $this->image_height,
            'source_url' => $this->source_url,
            'saves_count' => (int) ($this->saves_count ?? 0),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'is_saved' => (bool) ($this->is_saved_by_me ?? false),
            'is_liked' => (bool) ($this->is_liked_by_me ?? false),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
