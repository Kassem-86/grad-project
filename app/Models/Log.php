<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    protected $table = 'logs';
    protected $primaryKey = 'log_id';
    
    /**
     * Indicates if the primary key is auto-incrementing.
     * Set to false because we're using UUIDs.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     * Set to string because UUIDs are strings.
     *
     * @var string
     */
    protected $keyType = 'string';
    
    public $timestamps = true;

    protected $fillable = [
        'log_id',   
        'user_id',
        'log_title',
        'log_description',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    /**
     * Get the glucose readings associated with this log.
     */
    public function recordGlucoses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Glucose::class, 'log_id', 'log_id');
    }

    /**
     * Get the meals associated with this log.
     */
    public function recordMeals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Meal::class, 'log_id', 'log_id');
    }

    /**
     * Get the medications associated with this log.
     */
    public function recordMedications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecordMedication::class, 'log_id', 'log_id');
    }

    /**
     * Get the user associated with this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
