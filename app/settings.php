<?php
declare(strict_types=1);

use Monolog\Logger;
use Psr\Container\ContainerInterface;

return function (ContainerInterface $container) {
    // Global Settings Object
    $container->set('settings', [
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
            'host' => 'localhost',
            'user' => 'root',
            'pass' => '',
            'dbname' => 'deepgamers'
        ],

        'twitch' => [
            'clientID' => '',
            'clientSecret' => '',
            'accessTokenCacheKey' => 'twitch.accessToken'
        ]
    ]);
};
