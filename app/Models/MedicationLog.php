<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationLog extends Model
{
    protected $table = 'medication_logs';
    protected $primaryKey = 'medlog_id';

    protected $fillable = [
        'medication_id',
        'taken_at',
        'status',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
    ];

    /**
     * Get the medication associated with this log.
     */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class, 'medication_id', 'medication_id');
    }
}
