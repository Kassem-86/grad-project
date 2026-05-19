<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecordMedication extends Model
{
    protected $table = 'record_medications';
    protected $primaryKey = 'medication_id';
    public $timestamps = false;
protected $touches = ['log'];
    protected $fillable = [
        'log_id',
        'user_id',
        'notes',
        'medications',
    ];
    protected $hidden = ['selectedMedications'];
    protected $appends = ['medications'];

    protected $guarded = []; // 👈 ده معناه "اسمح بكتابة أي داتا جاية من غير حماية"
    protected $casts = [
        'medications' => 'array',
    ];

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
   public function getMedicationsAttribute()
    {
        // لو العلاقة مش معملولها load، رجع مصفوفة فاضية
        if (!$this->relationLoaded('selectedMedications')) {
            return [];
        }

        // بنلف على الأدوية ونرجع أوبجكتس فيها الـ name بس
        return $this->selectedMedications->map(function ($selectedMed) {
            return [
                'medication_name' => $selectedMed->medication_name
            ];
        })->values()->all();
    }
}

