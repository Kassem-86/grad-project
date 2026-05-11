<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecordMedication extends Model
{
    protected $table = 'record_medications';
    protected $primaryKey = 'medication_id';

    protected $fillable = [
        'log_id',
        'user_id',
        'notes',
    ];

    protected $casts = [];

    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class, 'log_id', 'log_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function selectedMedications(): HasMany
    {
        return $this->hasMany(SelectedMedication::class, 'medication_id', 'medication_id');
    }
}

