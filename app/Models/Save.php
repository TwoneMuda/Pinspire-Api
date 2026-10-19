<?php

namespace App\Models;

use Database\Factories\SaveFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'board_id', 'pin_id'])]
class Save extends Model
{
    /** @use HasFactory<SaveFactory> */
    use HasFactory;

    /**
     * The user who saved the pin.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The board the pin was saved to.
     *
     * @return BelongsTo<Board, $this>
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * The saved pin.
     *
     * @return BelongsTo<Pin, $this>
     */
    public function pin(): BelongsTo
    {
        return $this->belongsTo(Pin::class);
    }
}
