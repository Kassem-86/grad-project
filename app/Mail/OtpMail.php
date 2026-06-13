<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $name; // 1. لازم المتغير ده يكون معرف هنا

    public function __construct($otp, $name)
    {
        $this->otp = $otp;
        $this->name = $name; // 2. لازم يتسند هنا
    }

    public function build()
    {
        return $this->view('otp') // اسم ملف الـ blade
                    ->subject('Your Password Reset Code')
                    ->with([
                        'otp' => $this->otp,
                        'name' => $this->name, // 3. لازم تبعته للـ view من هنا
                    ]);
    }
}