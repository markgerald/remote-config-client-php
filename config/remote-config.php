<?php
return [
    'host'        => env('REMOTE_CONFIG_HOST', ''),
    'username'  => env('REMOTE_CONFIG_USERNAME', ''),
    'password'  => env('REMOTE_CONFIG_PASSWORD', ''),
    'application' => env('REMOTE_CONFIG_APPLICATION', ''),
    'environment' => env('REMOTE_CONFIG_ENVIRONMENT', 'development'),
];
