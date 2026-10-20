<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'password', 'avatar', 'bio', 'website', 'preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The default notification preferences for a new account.
     *
     * @var array<string, bool>
     */
    public const DEFAULT_PREFERENCES = [
        'email_notifications' => true,
        'push_notifications' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    /**
     * The pins uploaded by the user.
     *
     * @return HasMany<Pin, $this>
     */
    public function pins(): HasMany
    {
        return $this->hasMany(Pin::class);
    }

    /**
     * The boards owned by the user.
     *
     * @return HasMany<Board, $this>
     */
    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    /**
     * The pins saved by the user.
     *
     * @return HasMany<Save, $this>
     */
    public function saves(): HasMany
    {
        return $this->hasMany(Save::class);
    }

    /**
     * The likes left by the user.
     *
     * @return HasMany<PinLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PinLike::class);
    }

    /**
     * The comments written by the user.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * The users following this user.
     *
     * @return BelongsToMany<User, $this>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'following_id', 'follower_id')->withTimestamps();
    }

    /**
     * The users this user follows.
     *
     * @return BelongsToMany<User, $this>
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'follows', 'follower_id', 'following_id')->withTimestamps();
    }

    /**
     * The messages sent by the user.
     *
     * @return HasMany<Message, $this>
     */
    public function messagesSent(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * The messages received by the user.
     *
     * @return HasMany<Message, $this>
     */
    public function messagesReceived(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    /**
     * The in-app notifications for the user.
     *
     * @return HasMany<AppNotification, $this>
     */
    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    /**
     * Attach counters and the current viewer's follow flag.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function withViewerData(Builder $query, ?User $viewer = null): void
    {
        $query->withCount(['pins', 'boards', 'followers', 'following']);

        if ($viewer !== null) {
            $query->withExists([
                'followers as is_following' => fn (Builder $followers) => $followers->where('follows.follower_id', $viewer->id),
            ]);
        }
    }

    /**
     * The user's notification preferences merged with the application defaults.
     *
     * @return array<string, bool>
     */
    public function preferencesWithDefaults(): array
    {
        return array_merge(self::DEFAULT_PREFERENCES, $this->preferences ?? []);
    }
}
