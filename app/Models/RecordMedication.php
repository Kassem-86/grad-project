<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecordMedication extends Model
{
    protected $table = 'record_medications';
    protected $primaryKey = 'medication_id'; // 👈 المفتاح الأساسي
    public $incrementing = true;
    protected $keyType = 'int';
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
    'medication_id' => 'integer', 
];
    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class, 'log_id', 'log_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function selectedMedications() 
    {
        return $this->hasMany(SelectedMedication::class, 'medication_id', 'medication_id');
    }
  public function getMedicationsAttribute()
{
    if (!$this->relationLoaded('selectedMedications')) {
        return [];
    }

    // هيرجع مصفوفة أسماء مباشرة: ["tremadol", "panadol"]
    return $this->selectedMedications->pluck('medication_name')->all();
}
}

