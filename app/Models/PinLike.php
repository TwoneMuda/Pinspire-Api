<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'pin_id'])]
class PinLike extends Model
{
    /** @use HasFactory<\Database\Factories\PinLikeFactory> */
    use HasFactory;

    /**
     * The user who liked the pin.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The liked pin.
     *
     * @return BelongsTo<Pin, $this>
     */
    public function pin(): BelongsTo
    {
        return $this->belongsTo(Pin::class);
    }
}
