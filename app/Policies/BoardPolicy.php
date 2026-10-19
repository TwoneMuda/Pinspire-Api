<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    /**
     * Determine whether the user can view any model.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * Private boards are only visible to their owner.
     */
    public function view(?User $user, Board $board): bool
    {
        return ! $board->is_private || $user?->id === $board->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Board $board): bool
    {
        return $user->id === $board->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Board $board): bool
    {
        return $user->id === $board->user_id;
    }
}
