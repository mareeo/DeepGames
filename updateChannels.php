<?php

use App\Services\StreamUpdateService;
use DI\ContainerBuilder;
use Psr\SimpleCache\InvalidArgumentException;

require __DIR__ . '/vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

if (false) {
    $containerBuilder->enableCompilation(__DIR__ . '/var/cache');
}

$containerBuilder->addDefinitions(__DIR__ . '/app/settings.php');
$containerBuilder->addDefinitions(__DIR__ . '/app/settings.private.php');
$containerBuilder->addDefinitions(__DIR__ . '/app/dependencies.php');

// Build PHP-DI Container instance
try {
    $container = $containerBuilder->build();
    $service = $container->get(StreamUpdateService::class);
} catch (Throwable $e) {
    echo "Container error: " . get_class($e) . ' - ' . $e->getMessage() . "\n";
    error_log($e);
    die(1);
}


try {
    $service->updateTwitchChannels();
} catch (Throwable | InvalidArgumentException $e) {
    echo "Error updating twitch channels: " . get_class($e) . ' - ' . $e->getMessage() . "\n";
    error_log($e);
}


try {
    $service->updateAngelThumpChannels();
} catch (Throwable $e) {
    echo "Error updating AngelThump channels: " . $e->getMessage() . "\n";
    error_log($e);
}
