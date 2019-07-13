<?php

use DeepGamers\Integrations\AngelThumpIntegration;
use DeepGamers\Integrations\StreamInfo;
use DeepGamers\Integrations\TwitchIntegration;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\InvalidArgumentException;

require __DIR__ . '/vendor/autoload.php';

$container = new \DI\Container();

// Set up settings
$settings = require __DIR__ . '/app/settings.php';
$settings($container);

// Set up dependencies
$dependencies = require __DIR__ . '/app/dependencies.php';
$dependencies($container);

try {
    /** @var PDO $dbh */
    $dbh = $container->get('dbh');
    $twitch = $container->get(TwitchIntegration::class);
    updateTwitchChannels($dbh, $twitch);
} catch (\Throwable | GuzzleException | InvalidArgumentException $e ) {
    echo "Error updating twitch channels: " . get_class($e) . ' - ' . $e->getMessage() . "\n";
    error_log($e->getMessage());
}


try {
    $angelThump = $container->get(AngelThumpIntegration::class);
    updateAngelThumpChannels($dbh, $angelThump);
} catch (\Throwable $e) {
    echo "Error updating AngelThump channels: " . $e->getMessage() . "\n";
    error_log($e->getMessage());
}


function updateAngelThumpChannels(PDO $dbh, AngelThumpIntegration $angelThump)
{
    echo "Updating Angel Thump Channels...\n";

    $channels = getAngelThumpChannels($dbh);

    foreach ($channels as $username => $id) {
        echo "Updating $username";

        try {
            $streamInfo = $angelThump->getStreamInfo($username);
            updateDbRow($dbh, $id, $streamInfo);
        } catch (\Throwable $e) {
            echo "Error updating AngelThump channel $username: " . $e->getMessage() . "\n";
            error_log($e->getMessage());
        }
    }

}


/**
 * Get all twitch channels from the database, use the Twitch API to get updated info, the update the database.
 * @param PDO $dbh
 * @param TwitchIntegration $twitch
 * @throws GuzzleException
 * @throws InvalidArgumentException
 */
function updateTwitchChannels(PDO $dbh, TwitchIntegration $twitch): void
{
    echo "Updating twitch channels...\n";

    // Get all twitch channels from the database
    $twitchChannels = getTwitchChannels($dbh);
    $twitchUsernames = array_keys($twitchChannels);

    // Get current stream info from Twitch API
    $streamInfoMap = $twitch->getStreamInfo($twitchUsernames);

    // Update database with updated info
    foreach($streamInfoMap as $username => $streamInfo) {
        echo "Updating database record for $username...\n";
        $dbId = (int)$twitchChannels[$username];
        updateDbRow($dbh, $dbId, $streamInfo);
    }
}

/**
 * Update a database row with newer stream information.
 * @param PDO $dbh
 * @param int $id
 * @param StreamInfo $streamInfo
 */
function updateDbRow(PDO $dbh, int $id, StreamInfo $streamInfo): void
{
    $query = $dbh->prepare(<<<SQL
UPDATE stream
SET
  last_updated = CURRENT_TIMESTAMP,
  title = :title,
  game = :game,
  thumbnail = :thumbnail,
  live = :live,
  viewers = :viewers
WHERE
  stream_id = :id
SQL
    );

    $query->execute([
        ':id' => $id,
        ':title' => $streamInfo->title,
        ':game' => $streamInfo->game,
        ':thumbnail' => $streamInfo->thumbnail,
        ':live' => (int)$streamInfo->live,
        ':viewers' => $streamInfo->viewers
    ]);
}

/**
 * Get all twitch channels from the database
 * @param PDO $dbh
 * @return array A map where key is username and value is database ID.
 */
function getTwitchChannels(PDO $dbh): array
{
    $query = $dbh->prepare(<<<SQL
SELECT name, stream_id 
FROM stream 
WHERE service = 'twitch'
SQL
    );

    $query->execute();
    return $query->fetchAll(PDO::FETCH_KEY_PAIR);
}

/**
 * Get all twitch channels from the database
 * @param PDO $dbh
 * @return array A map where key is username and value is database ID.
 */
function getAngelThumpChannels(PDO $dbh): array
{
    $query = $dbh->prepare(<<<SQL
SELECT name, stream_id 
FROM stream 
WHERE service = 'angelthump'
SQL
    );

    $query->execute();
    return $query->fetchAll(PDO::FETCH_KEY_PAIR);
}
