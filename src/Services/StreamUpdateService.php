<?php


namespace App\Services;

use App\DB\ChannelRepository;
use App\DB\Stream;
use App\DB\StreamRepository;
use App\Integrations\AngelThumpApiClient;
use App\Integrations\TwitchApiClient;
use DateTimeImmutable;
use GuzzleHttp\Exception\GuzzleException;
use PDO;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class StreamUpdateService
{
    private PDO $pdo;
    private TwitchApiClient $twitch;
    private AngelThumpApiClient $angelThump;
    private ChannelRepository $channelRepository;
    private StreamRepository $streamRepository;


    public function __construct(PDO $pdo, TwitchApiClient $twitch, AngelThumpApiClient $angelThump)
    {
        $this->pdo = $pdo;
        $this->twitch = $twitch;
        $this->angelThump = $angelThump;
        $this->channelRepository = new ChannelRepository($this->pdo);
        $this->streamRepository = new StreamRepository($this->pdo);
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
        $twitchChannels = $this->channelRepository->getChannelsForService('twitch');

        $twitchUsernames = [];

        foreach ($twitchChannels as $twitchChannel) {
            $twitchUsernames[] = $twitchChannel->name;
        }

        $twitchUsernames[] = 'thonggdelonge';

        $liveStreams = [];
        $offlineUsers = [];

        // Twitch API calls allow a max of 100 streams per request
        foreach(array_chunk($twitchUsernames, 100) as $channelsChunk) {

            // Get current streams
            $streams = $this->twitch->getStreams($channelsChunk);

            $liveUsernames = [];

            foreach ($streams as $stream) {
                $liveStreams[$stream->user_login] = $stream;
                $liveUsernames[] = $stream->user_login;
            }

            // Get information for live and not live users$usernames and merge the results together
            $offlineUsernames = array_values(array_diff($channelsChunk, $liveUsernames));
            $users = $this->twitch->getUsers($offlineUsernames);

            foreach ($users as $user) {
                $offlineUsers[$user->login] = $user;
            }
        }

        var_dump($liveStreams);
        var_dump($offlineUsers);





        // Update database with updated info
        foreach($twitchChannels as $twitchChannel) {
            echo "Updating database record for $twitchChannel->name...\n";

            if (array_key_exists($twitchChannel->name, $liveStreams)) {
                $stream = $liveStreams[$twitchChannel->name];
                $thumbnail = str_replace('{width}x{height}', '320x180', $stream->thumbnail_url);
                $twitchChannel->lastUpdated = new DateTimeImmutable();
                $twitchChannel->title = $stream->title;
                $twitchChannel->subtitle = $stream->game_name;
                $twitchChannel->image = $thumbnail;
                $twitchChannel->live = true;
                $twitchChannel->viewers = $stream->viewer_count;
            } elseif (array_key_exists($twitchChannel->name, $offlineUsers)) {
                $user = $offlineUsers[$twitchChannel->name];
                $twitchChannel->lastUpdated = new DateTimeImmutable();
                $twitchChannel->title = $user->display_name;
                $twitchChannel->subtitle = '';
                $twitchChannel->image = $user->offline_image_url !== '' ? $user->offline_image_url : $user->profile_image_url;
                $twitchChannel->live = false;
                $twitchChannel->viewers = 0;
            } else {
                continue;
            }

            $this->channelRepository->saveChannel($twitchChannel);
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
                if ($streamInfo === null) {
                    $streamInfo = $this->angelThump->getUserInfo($username);
                }
                if ($streamInfo instanceof Stream) {
                    $this->updateDbRow($id, $streamInfo);
                }
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
            SELECT name, channel_id
            FROM channel 
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
        SQL);

        $query->execute();
        return $query->fetchAll(PDO::FETCH_KEY_PAIR);
    }

}