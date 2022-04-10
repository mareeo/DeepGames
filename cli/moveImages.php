<?php

use App\Services\ImgDumpService;
use App\Services\StreamUpdateService;
use DI\ContainerBuilder;
use Psr\SimpleCache\InvalidArgumentException;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

if (false) {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

$containerBuilder->addDefinitions(__DIR__ . '/../app/settings.php');
$containerBuilder->addDefinitions(__DIR__ . '/../app/settings.private.php');
$containerBuilder->addDefinitions(__DIR__ . '/../app/dependencies.php');

// Build PHP-DI Container instance
try {
    $container = $containerBuilder->build();
} catch (Throwable $e) {
    echo "Container error: " . get_class($e) . ' - ' . $e->getMessage() . "\n";
    error_log($e);
    die(1);
}

/** @var PDO */
$pdo = $container->get(PDO::class);

/** @var ImgDumpService */
$imgDumpService = $container->get(ImgDumpService::class);

$query = $pdo->prepare('SELECT * FROM imgdump');

$query->execute();

$results = $query->fetchAll();

$updateQuery = $pdo->prepare('UPDATE imgdump SET path = ? WHERE imgdump_id = ?');

foreach ($results as $row) {
    $id = $row['imgdump_id'];
    $originalPath = $row['path'];

    if (str_contains($originalPath, '/')) {
        continue;
    }

    $submitted = new DateTimeImmutable($row['submitted_timestamp']);

    $directory = $submitted->format('Y') . '/' . $submitted->format('n') . '/';

    $newPath = $directory . $originalPath;

    $absoluteDirectory = ImgDumpService::UPLOAD_DIRECTORY . '/' . $directory;

    $oldAbsolutePath = ImgDumpService::UPLOAD_DIRECTORY . '/' . $row['path'];
    $newAbsolutePath = ImgDumpService::UPLOAD_DIRECTORY . '/' . $newPath;

    if (!file_exists($oldAbsolutePath)) {
        continue;
    }

    echo "Moving $originalPath to $newPath\n";

    if (!is_dir($absoluteDirectory)) {
        mkdir($absoluteDirectory, 0777, true);
    }

    rename($oldAbsolutePath, $newAbsolutePath);

    $updateQuery->execute([$newPath, $id]);


    $parts = explode('.', $newPath);
    $imgDumpService->makeThumb($newPath, $parts[1]);
}