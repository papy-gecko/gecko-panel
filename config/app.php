<?php

return [

    'name' => env('APP_NAME', 'Pelican'),
    'ssh_harden_user' => env('SSH_HARDEN_USER', 'debian'),
    'logo' => env('APP_LOGO', '/gecko.png'),
    'favicon' => env('APP_FAVICON', '/pelican.ico'),

    'version' => '1.0.0',

    'timezone' => 'UTC',

    'installed' => env('APP_INSTALLED', true),

    'exceptions' => [
        'report_all' => env('APP_REPORT_ALL_EXCEPTIONS', false),
    ],

    'fallback_locale' => 'en',

];
