<?php

namespace App\Http\Resources;

use App\Models\Save;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Save
 */
class SaveResource extends JsonResource
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
            'board' => new BoardResource($this->whenLoaded('board')),
            'pin' => new PinResource($this->whenLoaded('pin')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
