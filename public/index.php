<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create(
    new \Nyholm\Psr7\Factory\Psr17Factory(),
    new \DI\Container()
);

// Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($app);

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($app);

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);



$app->run();
