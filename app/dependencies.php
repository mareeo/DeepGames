<?php
declare(strict_types=1);

use DeepGamers\Integrations\AngelThumpIntegration;
use DeepGamers\Integrations\TwitchIntegration;
use DI\Container;
use DI\ContainerBuilder;
use League\Plates\Engine;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get('settings');

            $loggerSettings = $settings['logger'];
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },

        Engine::class => function(ContainerInterface $c) {
            return Engine::create($c->get('settings')['renderer']['template_path']);
        },

        PDO::class => function(ContainerInterface $c) {
            $db = $c->get('settings')['db'];
            return new PDO('mysql:host='.$db['host'].';dbname='.$db['dbname'], $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        },

        CacheInterface::class => function () {
            return new Psr16Cache(
                new PhpFilesAdapter('', 0, __DIR__ . '/../var/cache')
            );
        },

        TwitchIntegration::class => function(ContainerInterface $c) {
            $twitchSettings = $c->get('settings')['twitch'];

            $cache = $c->get(CacheInterface::class);
            return new TwitchIntegration($cache, $twitchSettings['clientID'], $twitchSettings['clientSecret'], $twitchSettings['accessTokenCacheKey']);
        },

        AngelThumpIntegration::class => function() {
            return new AngelThumpIntegration();
        }





    ]);
};
