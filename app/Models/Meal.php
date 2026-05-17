<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meal extends Model
{
    protected $table = 'record_meals';
    protected $primaryKey = 'meal_id';
    public $timestamps = false;

    protected $fillable = [
        'log_id',
        'user_id',
        'total_carb',
        'total_calories',
        'meal_type',
        'meal_description',
        'notes',
    ];

    protected $casts = [
        'total_carb' => 'float',
        'total_calories' => 'float',
    ];

    /**
     * Get the log associated with this meal.
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class, 'log_id', 'log_id');
    }

    /**
     * Get the user associated with this meal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

