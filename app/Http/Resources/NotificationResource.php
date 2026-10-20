<?php

namespace App\Http\Resources;

use App\Models\AppNotification;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AppNotification
 */
class NotificationResource extends JsonResource
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
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'image_url' => Media::url($this->image_path),
            'is_read' => $this->read_at !== null,
            'actor' => new UserResource($this->whenLoaded('actor')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
