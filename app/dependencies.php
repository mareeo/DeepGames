<?php
declare(strict_types=1);

use DeepGamers\Integrations\AngelThumpIntegration;
use DeepGamers\Integrations\TwitchIntegration;
use DI\Container;
use League\Plates\Engine;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;


return function (ContainerInterface $container) {
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

    $container->set(Engine::class, function(Container $c) {
        return Engine::create($c->get('settings')['renderer']['template_path']);
    });

    $container->set('dbh', function(Container $c) {
        $db = $c->get('settings')['db'];
        $pdo = new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['user'], $db['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    });

    $container->set(/**
     * @return Psr16Cache
     */
        'cache', function () {
        return new Psr16Cache(
            new PhpFilesAdapter('', 0, __DIR__ . '/../cache')
        );
    });

    $container->set(TwitchIntegration::class, function(Container $c) {
        $twitchSettings = $c->get('settings')['twitch'];

        /** @var CacheInterface $cache */
        $cache = $c->get('cache');
        return new TwitchIntegration($cache, $twitchSettings['clientID'], $twitchSettings['clientSecret'], $twitchSettings['accessTokenCacheKey']);
    });

    $container->set(AngelThumpIntegration::class, function(Container $c) {
        return new AngelThumpIntegration();
    });
};
