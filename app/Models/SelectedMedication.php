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

    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($selectedMedication) {
            \Illuminate\Support\Facades\DB::table('sync_deletions')->insert([
                'user_id' => $selectedMedication->user_id,
                'table_name' => 'selected_medications',
                'record_id' => (string) $selectedMedication->selected_med_id,
                'deleted_at' => now(),
            ]);
        });
    }

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

