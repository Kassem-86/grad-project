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
        'first_name',
        'last_name',
        'email',
        'password',
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
}
