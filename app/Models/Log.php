<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    protected $table = 'logs';
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'user_id',
    ];

    /**
     * Get the glucose reading associated with this log.
     */
    public function glucose(): HasOne
    {
        return $this->hasOne(Glucose::class, 'log_id', 'log_id');
    }

    /**
     * Get the meal associated with this log.
     */
    public function meal(): HasOne
    {
        return $this->hasOne(Meal::class, 'log_id', 'log_id');
    }

    /**
     * Get the medication associated with this log.
     */
    public function medication(): HasOne
    {
        return $this->hasOne(Medication::class, 'log_id', 'log_id');
    }

    /**
     * Get the user associated with this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
