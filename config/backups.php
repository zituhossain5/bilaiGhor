<?php

return [
    // Optional absolute path for hosts where the dump binary is not on PATH.
    'mysqldump_path' => env('MYSQLDUMP_PATH'),
    'drive' => [
        'enabled' => (bool) env('GOOGLE_DRIVE_BACKUP_ENABLED', false),
        'folder_id' => env('GOOGLE_DRIVE_BACKUP_FOLDER_ID'),
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI'),
    ],
    'retention_days' => max(1, (int) env('BACKUP_RETENTION_DAYS', 14)),
    'daily_time' => env('BACKUP_DAILY_TIME', '02:00'),
    'timezone' => env('BACKUP_TIMEZONE', 'Asia/Dhaka'),
    'encryption' => [
        'enabled' => (bool) env('BACKUP_ENCRYPTION_ENABLED', false),
        'key' => env('BACKUP_ENCRYPTION_KEY'),
    ],
];
