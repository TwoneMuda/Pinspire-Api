<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    /**
     * Create an in-app notification for a user.
     */
    public static function push(
        User $recipient,
        ?User $actor,
        string $type,
        string $title,
        ?string $body = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?string $imagePath = null,
    ): void {
        if ($actor !== null && $actor->id === $recipient->id) {
            return;
        }

        AppNotification::create([
            'user_id' => $recipient->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'image_path' => $imagePath,
        ]);
    }
}
