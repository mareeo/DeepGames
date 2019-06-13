<?php
declare(strict_types=1);

use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Slim\App;

return function (App $app) {
    /** @var Container $container */
    $container = $app->getContainer();

    $container->set(LoggerInterface::class, function (Container $c) {
        $settings = $c->get('settings');

        $loggerSettings = $settings['logger'];
        $logger = new Logger($loggerSettings['name']);

        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
        $logger->pushHandler($handler);

        return $logger;
    });

    $container->set(\League\Plates\Engine::class, function(Container $c) {
        return \League\Plates\Engine::create($c->get('settings')['renderer']['template_path']);
    });

    $container->set('dbh', function(Container $c) {
        $db = $c->get('settings')['db'];
        $pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['user'], $db['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    });
};
