<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Reminder extends Model
{    use FormatsDates;

    /** @use HasFactory<\Database\Factories\ReminderFactory> */
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    // Enum values for message_type
    public const MESSAGE_TYPES = [
        'medication',
        'glucose_check',
        'meal',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'message_type',
        'medication_name',
        'time',
        'status',
        'record_id'
        
    ];

    protected $casts = [
        'time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    
    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($reminder) {
            \Illuminate\Support\Facades\DB::table('sync_deletions')->insert([
                'user_id' => $reminder->user_id,
                'table_name' => 'reminders',
                'record_id' => (string) $reminder->getKey(),
                'deleted_at' => now(),
            ]);
        });
    }

    /**
     * Get the user that owns the reminder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
}
