<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */
'paths' => ['api/*', 'sanctum/csrf-cookie', "broadcasting/auth"],
'allowed_methods' => ['*'],
'allowed_origins' => [
    'http://localhost:5173', 
    'http://localhost:3000', 
    'http://localhost:5174', 
    'https://seen-lime.vercel.app',
    'https://inquisitorial-elba-undistractedly.ngrok-free.dev',
    
],

'allowed_origins_patterns' => [
    '#^android://.*#', // ده الـ Regex الصح اللي لارفيل بتفهمه للموبايل
    '#^capacitor://.*#',
],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true, // لازم تكون true طالما فيه Login
    

];
