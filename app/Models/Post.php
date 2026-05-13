<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    /**
     * Post category constants
     */
    public const CATEGORIES = ['General', 'Type1 / LADA', 'Type2', 'MODY', 'Gestational', 'Advices'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'category',
        'likes_count',
        'comments_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that created the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comments for the post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the likes for the post (polymorphic).
     */
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Get the images for this post.
     */
    public function images(): HasMany
    {
        return $this->hasMany(PostImage::class);
    }


    /**
     * Get whether the authenticated user likes this post.
     * 
     * Uses the 'is_liked' attribute set by withExists() in queries.
     * Falls back to false if not set (unauthenticated or not eager-loaded).
     */
    public function getIsLikedAttribute(): bool
    {
        // If the attribute was set via withExists(), use it
        if ($this->attributes['is_liked'] ?? false) {
            return true;
        }

        // Otherwise return false (not authenticated or not loaded)
        return false;
    }
}
