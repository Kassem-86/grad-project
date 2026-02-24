<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordMealFoodItem extends Model
{
    protected $table = 'record_meal_food_items';
    protected $primaryKey = 'food_id';

    protected $fillable = [
        'meal_id',
        'food_name',
        'quantity',
        'carbs',
        'protein',
        'fats',
        'calories',
    ];

    protected $casts = [
        'quantity' => 'float',
        'carbs' => 'float',
        'protein' => 'float',
        'fats' => 'float',
        'calories' => 'float',
    ];

    /**
     * Get the meal associated with this food item.
     */
    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class, 'meal_id', 'meal_id');
    }
}
