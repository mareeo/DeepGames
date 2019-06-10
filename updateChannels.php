<?php

use DeepGamers\Integrations\StreamInfo;
use DeepGamers\Integrations\TwitchIntegration;

require __DIR__ . '/vendor/autoload.php';

$dbh = new PDO('mysql:host=localhost;dbname=deepgamers', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

try {
    updateTwitchChannels($dbh);
} catch (Throwable $e) {
    echo "Error updating twitch channnels: " . $e->getMessage();
    error_log($e->getMessage());
}


/**
 * Get all twitch channels from the database, use the Twitch API to get updated info, the update the database.
 * @param PDO $dbh
 * @throws Exception
 */
function updateTwitchChannels(PDO $dbh): void
{
    echo "Updating twitch channels...\n";

    // Get all twitch channels from the database
    $twitchChannels = getTwitchChannels($dbh);
    $twitchUsernames = array_keys($twitchChannels);

    // Get current stream info from Twitch API
    $twitch = new TwitchIntegration('');
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
