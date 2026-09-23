<?php

return [
    'default' => env('FILESYSTEM_DISK', 'public'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'slips' => [
            'driver' => 'local',
            'root' => storage_path('app/slips'),
            'visibility' => 'private',
            'throw' => false,
        ],
    ],


    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
