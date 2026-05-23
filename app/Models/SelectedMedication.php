<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SelectedMedication extends Model
{
    protected $table = 'selected_medications';
    protected $primaryKey = 'selected_med_id';

    protected $fillable = [
        'user_id',
        'medication_name',
    ];

    public function recordMedications()
    {
        return $this->belongsToMany(RecordMedication::class, 'medication_log_pivot', 'selected_medication_id', 'record_medication_id');
    }

    // public function log(): BelongsTo
    // {
    //     return $this->belongsTo(Log::class, 'log_id', 'log_id');
    // }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

