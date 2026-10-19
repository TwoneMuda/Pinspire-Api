<?php

namespace App\Models;

use Database\Factories\PinFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'title', 'description', 'image_path', 'source_url', 'image_width', 'image_height'])]
class Pin extends Model
{
    /** @use HasFactory<PinFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'image_width' => 'integer',
            'image_height' => 'integer',
        ];
    }

    /**
     * The user who uploaded the pin.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The saves that reference this pin.
     *
     * @return HasMany<Save, $this>
     */
    public function saves(): HasMany
    {
        return $this->hasMany(Save::class);
    }

    /**
     * Attach the save counter and the current viewer's "saved" flag.
     *
     * @param  Builder<Pin>  $query
     */
    #[Scope]
    protected function withViewerData(Builder $query, ?User $viewer = null): void
    {
        $query->withCount('saves');

        if ($viewer !== null) {
            $query->withExists([
                'saves as is_saved_by_me' => fn (Builder $saves) => $saves->where('user_id', $viewer->id),
            ]);
        }
    }

    /**
     * Filter pins by a free text term on their title or description.
     *
     * @param  Builder<Pin>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }
}
