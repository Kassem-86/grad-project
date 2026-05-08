<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelectedMedication extends Model
{
    protected $table = 'selected_medications';
    protected $primaryKey = 'selected_med_id';

    protected $fillable = [
        'medication_id',
        'log_id',
        'user_id',
        'medication_name',
    ];

    public function recordMedication(): BelongsTo
    {
        return $this->belongsTo(RecordMedication::class, 'medication_id', 'medication_id');
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class, 'log_id', 'log_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

