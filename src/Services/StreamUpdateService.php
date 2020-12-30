<?php


namespace App\Services;


use App\DB\Stream;
use App\Integrations\AngelThump\AngelThumpApi;
use App\Integrations\Twitch\TwitchApi;
use GuzzleHttp\Exception\GuzzleException;
use PDO;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class StreamUpdateService
{
    private PDO $pdo;
    private TwitchApi $twitch;
    private AngelThumpApi $angelThump;

    public function __construct(PDO $pdo, TwitchApi $twitch, AngelThumpApi $angelThump)
    {
        $this->pdo = $pdo;
        $this->twitch = $twitch;
        $this->angelThump = $angelThump;
    }

    /**
     * Get all twitch channels from the database, use the Twitch API to get updated info, the update the database.
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function updateTwitchChannels(): void
    {
        echo "Updating twitch channels...\n";

        // Get all twitch channels from the database
        $twitchChannels = $this->getTwitchChannels();
        $twitchUsernames = array_keys($twitchChannels);

        // Get current stream info from Twitch API
        $streamInfoMap = $this->twitch->getStreamInfo($twitchUsernames);

        // Update database with updated info
        foreach($streamInfoMap as $username => $streamInfo) {
            echo "Updating database record for $username...\n";
            $dbId = (int)$twitchChannels[$username];
            $this->updateDbRow($dbId, $streamInfo);
        }
    }

    public function updateAngelThumpChannels()
    {
        echo "Updating Angel Thump Channels...\n";

        $channels = $this->getAngelThumpChannels();

        foreach ($channels as $username => $id) {
            echo "Updating $username\n";

            try {
                $streamInfo = $this->angelThump->getStreamInfo($username);
                $this->updateDbRow($id, $streamInfo);
            } catch (Throwable $e) {
                echo "Error updating AngelThump channel $username: " . $e->getMessage() . "\n";
                error_log($e);
            }
        }
    }

    /**
     * Update a database row with newer stream information.
     * @param int $id
     * @param Stream $stream
     */
    private function updateDbRow(int $id, Stream $stream): void
    {
        $query = $this->pdo->prepare(<<<SQL
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
            ':title' => $stream->title,
            ':game' => $stream->game,
            ':thumbnail' => $stream->thumbnail,
            ':live' => (int)$stream->live,
            ':viewers' => $stream->viewers
        ]);
    }

    /**
     * Get all Twitch channels from the database
     * @return array A map where key is username and value is database ID.
     */
    private function getTwitchChannels(): array
    {
        $query = $this->pdo->prepare(<<<SQL
SELECT name, stream_id 
FROM stream 
WHERE service = 'twitch'
SQL
        );

        $query->execute();
        return $query->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Get all AngelThump channels from the database
     * @return array A map where key is username and value is database ID.
     */
    function getAngelThumpChannels(): array
    {
        $query = $this->pdo->prepare(<<<SQL
SELECT name, stream_id 
FROM stream 
WHERE service = 'angelthump'
SQL
        );

        $query->execute();
        return $query->fetchAll(PDO::FETCH_KEY_PAIR);
    }

}