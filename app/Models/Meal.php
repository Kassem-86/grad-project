<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meal extends Model
{
    protected $table = 'record_meals';
    protected $primaryKey = 'meal_id';

    protected $fillable = [
        'log_id',
        'meal_time',
        'total_carb',
        'total_calories',
        'meal_type',
        'notes',
    ];

    protected $casts = [
        'total_carb' => 'float',
        'total_calories' => 'float',
        'meal_time' => 'datetime',
    ];

    /**
     * Get the log associated with this meal.
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class, 'log_id', 'log_id');
    }

    /**
     * Get the food items associated with this meal.
     */
    public function foodItems(): HasMany
    {
        return $this->hasMany(RecordMealFoodItem::class, 'meal_id', 'meal_id');
    }
}
