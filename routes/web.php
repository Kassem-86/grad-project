<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| 
| This is an API-first backend for a Diabetes App.
| All application logic is served via the /api routes.
|
*/

Route::get('/', function () {
    return view('welcome');
});
