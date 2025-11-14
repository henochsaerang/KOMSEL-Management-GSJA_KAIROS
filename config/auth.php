<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */
   'defaults' => [
        'guard' => 'web',
        'passwords' => 'users', 
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users', 
        ],
    ],

    'providers' => [
        'users' => [ 
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [ 
            'provider' => 'users',
            'table' => 'password_reset_tokens', 
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */
    'password_timeout' => 10800,
];