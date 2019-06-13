<?php
declare(strict_types=1);

use DI\Container;
use Monolog\Logger;
use Slim\App;

return function (App $app) {
    /** @var Container $container */
    $container = $app->getContainer();

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
        ]
    ]);
};
