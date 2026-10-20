<?php

namespace App\Models;

use Database\Factories\PinFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'title',
    'description',
    'category',
    'vibe',
    'tags',
    'palette',
    'image_path',
    'image_url',
    'source_url',
    'image_width',
    'image_height',
])]
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
            'tags' => 'array',
            'palette' => 'array',
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
     * The likes left on this pin.
     *
     * @return HasMany<PinLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PinLike::class);
    }

    /**
     * The users who liked this pin.
     *
     * @return BelongsToMany<User, $this>
     */
    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pin_likes')->withTimestamps();
    }

    /**
     * The comments written on this pin.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Attach counters and the current viewer's interaction flags.
     *
     * @param  Builder<Pin>  $query
     */
    #[Scope]
    protected function withViewerData(Builder $query, ?User $viewer = null): void
    {
        $query->withCount(['saves', 'likes', 'comments']);

        if ($viewer !== null) {
            $query->withExists([
                'saves as is_saved_by_me' => fn (Builder $saves) => $saves->where('user_id', $viewer->id),
                'likes as is_liked_by_me' => fn (Builder $likes) => $likes->where('user_id', $viewer->id),
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
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('category', 'like', "%{$term}%")
                ->orWhere('vibe', 'like', "%{$term}%")
                ->orWhere('tags', 'like', "%{$term}%");
        });
    }
}
