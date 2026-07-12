<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'guard' => 'admin', // ✅ Default guard set to admin
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    */
    'guards' => [
        // ✅ For Admins (User table)
        'admin' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Optional Web (if frontend login needed)
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // ✅ For Customers (Customer table)
        'customer' => [
            'driver' => 'session',
            'provider' => 'customers',
        ],

        'delivery_boy' => [
            'driver' => 'session',
            'provider' => 'delivery_boys',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        // Admin / Users table
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // Customers table
        'customers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Customer::class,
        ],

        'delivery_boys' => [
            'driver' => 'eloquent',
            'model' => App\Models\DeliveryBoy::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Configurations
    |--------------------------------------------------------------------------
    */
'passwords' => [
    // ✅ Admin / User password reset
    'admins' => [
        'provider' => 'users', // একই ইউজার টেবিল ব্যবহার করবে
        'table' => 'password_resets',
        'expire' => 60,
        'throttle' => 60,
    ],

    // ✅ Customer password reset
    // নিজস্ব টেবিল — 'password_resets' admins broker-ও ব্যবহার করে, একই ইমেইল থাকলে
    // টোকেন একে অপরকে ওভাররাইট করত।
    'customers' => [
        'provider' => 'customers',
        'table' => 'customer_password_resets',
        'expire' => 60,   // link valid 60 minutes
        'throttle' => 60, // 60s between reset-link requests
    ],

    'delivery_boys' => [
        'provider' => 'delivery_boys',
        'table' => 'delivery_boy_password_resets',
        'expire' => 60,
        'throttle' => 60,
    ],
],

    'password_timeout' => 10800,

];
