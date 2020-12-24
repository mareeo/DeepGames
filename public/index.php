<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\CallableResolver;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

if (true) {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

$containerBuilder->addDefinitions(__DIR__ . '/../app/settings.php');
$containerBuilder->addDefinitions(__DIR__ . '/../app/dependencies.php');

// Build PHP-DI Container instance
$container = $containerBuilder->build();

$responseFactory = new Psr17Factory();
$callableResolver = new CallableResolver($container);
$routeCollector = new Slim\Routing\RouteCollector($responseFactory, $callableResolver, $container, null, null, __DIR__ . '/../var/cache/route.cache');

AppFactory::setResponseFactory($responseFactory);
AppFactory::setContainer($container);
AppFactory::setCallableResolver($callableResolver);
AppFactory::setRouteCollector($routeCollector);

$app = AppFactory::create();

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

$app->run();
