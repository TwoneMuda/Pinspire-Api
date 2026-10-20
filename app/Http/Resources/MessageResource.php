<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
class MessageResource extends JsonResource
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
            'body' => $this->body,
            'mine' => $request->user()?->id === $this->sender_id,
            'read_at' => $this->read_at?->toISOString(),
            'sender' => new UserResource($this->whenLoaded('sender')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
