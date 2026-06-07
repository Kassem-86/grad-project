<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostImage extends Model
{    use FormatsDates;

    protected $fillable = ['post_id', 'image_path', 'user_id'];

/**
     * Get the post that owns this image.
     */    
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
    
}
