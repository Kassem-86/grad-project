<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    protected $table = 'logs';
    protected $primaryKey = 'log_id';
    
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

        protected $fillable = [
            'log_id',   
            'user_id',
            'log_title',
            'log_description',
            'logged_at',
        ];
    protected $guarded = []; // 👈 ده معناه "اسمح بكتابة أي داتا جاية من غير حماية"
    protected $casts = [
        'logged_at' => 'datetime',
    ];

    /**
     * تعديل: قراءة سكر واحدة فقط لكل لوج
     */
    public function recordGlucose(): HasOne
    {
        return $this->hasOne(Glucose::class, 'log_id', 'log_id');
    }

    /**
     * تعديل: وجبة واحدة فقط لكل لوج
     */
    public function recordMeal(): HasOne
    {
        return $this->hasOne(Meal::class, 'log_id', 'log_id');
    }

    /**
     * تعديل: دواء واحد فقط لكل لوج
     */
    public function recordMedication(): HasOne
    {
        return $this->hasOne(RecordMedication::class, 'log_id', 'log_id');
    }

    /**
     * Get the user associated with this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}