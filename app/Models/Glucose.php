<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Glucose extends Model
{
    protected $table = 'record_glucose';
    protected $primaryKey = 'reading_id';

    protected $fillable = [
        'log_id',
        'glucose_level',
        'reading_time',
        'reading_type',
        'notes',
        'a1c_estimation',
        'average_glucose_level',
    ];

    protected $casts = [
        'glucose_level' => 'float',
        'reading_time' => 'datetime',
    ];

    /**
     * Get the log associated with this glucose reading.
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class, 'log_id', 'log_id');
    }
}
