<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Diabetes type constants
     */
    public const DIABETES_TYPES = ['Type1', 'LADA', 'Type2', 'MODY', 'Gestational'];

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
     * Get the notifications for the user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
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
     */// جوه ملف app/Models/User.php
public function friends(): \Illuminate\Support\Collection // 👈 غيرنا دي هنا عشان تتوافق مع الـ merge والـ pluck
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
        'device_token'
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

    public function recordGlucoses(): HasMany
    {
        return $this->hasMany(Glucose::class, 'user_id', 'id');
    }

    public function recordMeals(): HasMany
    {
        return $this->hasMany(Meal::class, 'user_id', 'id');
    }

    public function recordMedications(): HasMany
    {
        return $this->hasMany(RecordMedication::class, 'user_id', 'id');
    }

    public function selectedMedications(): HasMany
    {
        return $this->hasMany(SelectedMedication::class, 'user_id', 'id');
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
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    /**
     * Get the reminders for this user.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Get the chatbot messages for the user.
     */
    public function chatbotMessages(): HasMany
    {
        return $this->hasMany(Chatbot::class, 'user_id');
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
    public function friendsOf():BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
                    ->withPivot('status')
                    ->wherePivot('status', 'accepted');
    }
public function friendsWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
                    ->withPivot('status')
                    ->wherePivot('status', 'accepted');
    }
    /**
     * Get the profile picture URL.
     */
  public function getProfilePictureAttribute($value): string
{
    // If there is a value in the database, let Laravel's Storage handle the pathing
    if ($value) {
        return asset(Storage::url($value));
    }

    // Fallback if no profile picture is set
    return asset('storage/profiles/default.png');
}
}