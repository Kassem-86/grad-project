<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Get the posts created by the user.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the comments created by the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the likes made by the user.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Friendships initiated by this user.
     */
    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    /**
     * Friendships received by this user.
     */
    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Pending friend requests where this user is the target.
     */
    public function pendingFriendRequests(): HasMany
    {
        return $this->receivedFriendRequests()->where('status', 'pending');
    }

    /**
     * Outgoing accepted friendships.
     */
    public function acceptedOutgoingFriendships(): HasMany
    {
        return $this->sentFriendRequests()->where('status', 'accepted');
    }

    /**
     * Incoming accepted friendships.
     */
    public function acceptedIncomingFriendships(): HasMany
    {
        return $this->receivedFriendRequests()->where('status', 'accepted');
    }

    /**
     * All accepted friends for this user, in either direction.
     */
    public function friends(): EloquentCollection
    {
        return $this->acceptedOutgoingFriendships()
            ->with('friend')
            ->get()
            ->pluck('friend')
            ->merge(
                $this->acceptedIncomingFriendships()
                    ->with('user')
                    ->get()
                    ->pluck('user')
            )
            ->unique('id')
            ->values();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'profile_picture',
        'email',
        'password',
        'gender',
        'phone',
        'birthDate',
        'diabetes_type',
        'insulin_therapy',
        'diagnose_date',
        'glucose',
        'weight',
        'height',
        'max_glucose',
        'target_glucose_range',
        'min_glucose',
        'emergency_contact',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }

    /**
     * Get all logs associated with this user.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'user_id', 'id');
    }

    /**
     * Users blocked by this user.
     */
    public function blockedUsers(): HasMany
    {
        return $this->hasMany(Block::class, 'user_id');
    }

    /**
     * Users who blocked this user.
     */
    public function blockers(): HasMany
    {
        return $this->hasMany(Block::class, 'blocked_id');
    }

    /**
     * Get a merged list of all user IDs that this user should be restricted from.
     * Includes people I blocked and people who blocked me.
     */
    public function getRestrictedUserIds(): array
    {
        $blockedByMe = $this->blockedUsers()->pluck('blocked_id')->toArray();
        $blockedMe = $this->blockers()->pluck('user_id')->toArray();

        return array_unique(array_merge($blockedByMe, $blockedMe));
    }

    /**
     * Get the user's conversations.
     */
    public function conversations()
    {
        return Conversation::where('user1_id', $this->id)
            ->orWhere('user2_id', $this->id)
            ->orderByDesc('last_updated');
    }

    /**
     * Get the profile picture URL.
     */
    public function getProfilePictureAttribute(): string
    {
        return $this->attributes['profile_picture']
            ? asset('storage/profiles/' . $this->attributes['profile_picture'])
            : asset('storage/profiles/default.png');
    }
}
