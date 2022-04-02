<?php
declare(strict_types=1);

use Monolog\Logger;

return [
    'settings' => [
        'displayErrorDetails' => true, // Should be set to false in production

        'logger.name' => 'slim-app',
        'logger.path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
        'logger.level' => Logger::DEBUG,

        // Renderer settings
        'renderer.template_path' => __DIR__ . '/../templates',

        // Database settings
        'db.host' => 'localhost',
        'db.dbname' => 'deepgamers',

        'twitch.clientID' => '',
        'twitch.clientSecret' => '',
        'twitch.accessTokenCacheKey' => 'twitch.accessToken'
    ]
];
