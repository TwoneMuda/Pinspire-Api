<?php

namespace App\Policies;

use App\Models\Pin;
use App\Models\User;

class PinPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pin $pin): bool
    {
        return $user->id === $pin->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pin $pin): bool
    {
        return $user->id === $pin->user_id;
    }
}
