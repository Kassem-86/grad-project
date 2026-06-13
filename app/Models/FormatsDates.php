<?php

namespace App\Models;

use Carbon\Carbon;

trait FormatsDates
{
    // ده بيشيل الـ T و الـ Z وبيظبط التوقيت لمصر

    
    protected function serializeDate(\DateTimeInterface $date)
    {
        // غيرنا H لـ h وضفنا A
        return Carbon::instance($date)->setTimezone('Africa/Cairo')->format('Y-m-d h:i:s A');
    }         

    // عشان أي تاريخ يخرج من الموديل دايماً يكون بالتنسيق ده
    public function getCreatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Africa/Cairo')->format('Y-m-d h:i:s A') : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? Carbon::parse($value)->setTimezone('Africa/Cairo')->format('Y-m-d h:i:s A') : null;
    }
}