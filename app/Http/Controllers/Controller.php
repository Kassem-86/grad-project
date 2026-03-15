<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // 👈 لازم السطر ده
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests; // 👈 ولازم السطر ده عشان الـ authorize() تشتغل
}