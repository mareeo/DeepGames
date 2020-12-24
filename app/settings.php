<?php
declare(strict_types=1);

use Monolog\Logger;

return [
    'settings' => [
        'displayErrorDetails' => true, // Should be set to false in production
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => Logger::DEBUG,
        ],

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Database settings
        'db' => [
            'host' => '',
            'user' => '',
            'pass' => '',
            'dbname' => ''
        ],

        'twitch' => [
            'clientID' => 'ere8uvpivoc6kmq7nsa7j0ivw9vrse',
            'clientSecret' => '7gk42syi9y8iayzcjiizh49m0ijjwt',
            'accessTokenCacheKey' => 'twitch.accessToken'
        ]
    ]
];
