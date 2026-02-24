<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    protected $table = 'medications';
    protected $primaryKey = 'medication_id';

    protected $fillable = [
        'log_id',
        'medication_name',
        'dosage',
        'unit',
        'frequency',
        'route',
        'start_date',
        'end_date',
        'reminder_time',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        // 'reminder_time' => 'time', // ملحوظة: لاراڤل ما عندوش كاست اسمه time مباشرة، بنسيبها string أو كاست مخصص
    ];

    /**
     * العلاقة مع جدول الـ Logs (1:1)
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class, 'log_id', 'log_id');
    }

    /**
     * العلاقة مع جدول الـ MedicationLogs (1:N)
     * عشان نتابع المريض أخد الدواء ولا لأ
     */
    public function medicationLogs(): HasMany
    {
        return $this->hasMany(MedicationLog::class, 'medication_id', 'medication_id');
    }
}