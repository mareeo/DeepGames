<?php
declare(strict_types=1);

use App\DB\ChannelRepository;
use App\DB\StreamRepository;
use App\Integrations\AngelThumpApiClient;
use App\Integrations\TwitchApiClient;
use App\Services\StreamUpdateService;
use League\Plates\Engine;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;
use function DI\autowire;

return [
    LoggerInterface::class => function (ContainerInterface $c) {
        $settings = $c->get('settings');

        $logger = new Logger($settings['logger.name']);

        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler($settings['logger.path'], $settings['logger.level']);
        $logger->pushHandler($handler);

        return $logger;
    },

    Engine::class => function(ContainerInterface $c) {
        return new Engine($c->get('settings')['renderer.template_path']);
    },

    PDO::class => function(ContainerInterface $c) {
        $settings = $c->get('settings');
        return new PDO('mysql:host='.$settings['db.host'].';dbname='.$settings['db.dbname'], $settings['db.user'], $settings['db.pass'], [
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

    TwitchApiClient::class => function(ContainerInterface $c) {
        $settings = $c->get('settings');

        $cache = $c->get(CacheInterface::class);
        return new TwitchApiClient($cache, $settings['twitch.clientID'], $settings['twitch.clientSecret'], $settings['twitch.accessTokenCacheKey']);
    },

    AngelThumpApiClient::class => autowire(),
    StreamUpdateService::class => autowire(),
    ChannelRepository::class => autowire(),
    StreamRepository::class => autowire()


];

