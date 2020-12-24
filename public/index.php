<?php
declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new \DI\ContainerBuilder();

if (true) {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

$containerBuilder->addDefinitions(__DIR__ . '/../app/settings.php');
$containerBuilder->addDefinitions(__DIR__ . '/../app/dependencies.php');

// Build PHP-DI Container instance
$container = $containerBuilder->build();

$app = AppFactory::create(
    new Psr17Factory(),
    $container
);

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

$app->run();
