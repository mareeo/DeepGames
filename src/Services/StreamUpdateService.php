<?php


namespace App\Services;

use App\DB\ChannelRepository;
use App\DB\Stream;
use App\DB\StreamRepository;
use App\Integrations\AngelThumpApiClient;
use App\Integrations\TwitchApiClient;
use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Exception\GuzzleException;
use PDO;
use Psr\SimpleCache\InvalidArgumentException;

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
     * Get all twitch channels from the database, use the Twitch API to get updated info, then update the database.
     * 
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

        $liveStreamsMap = [];
        $offlineUsersMap = [];

        // Get current streams
        $streams = $this->twitch->getStreams($twitchUsernames);

        $liveUsernames = [];

        foreach ($streams as $stream) {
            $liveStreamsMap[$stream->user_login] = $stream;
            $liveUsernames[] = $stream->user_login;
        }

        // For not live channels get the user information
        $offlineUsernames = array_values(array_diff($twitchUsernames, $liveUsernames));
        $users = $this->twitch->getUsers($offlineUsernames);

        foreach ($users as $user) {
            $offlineUsersMap[$user->login] = $user;
        }

        $currentStreams = $this->streamRepository->getCurrentStreams();

        /** @var Stream[] */
        $currentStreamMap = [];

        // Create map of channel ID to stream
        foreach ($currentStreams as $currentStream) {
            $currentStreamMap[$currentStream->channelId] = $currentStream;
        }

        // Update database with updated info
        foreach($twitchChannels as $twitchChannel) {
            echo "Updating database record for $twitchChannel->name...\n";

            // If the channel is live on twitch
            if (array_key_exists($twitchChannel->name, $liveStreamsMap)) {
                $stream = $liveStreamsMap[$twitchChannel->name];
                $thumbnail = str_replace('{width}x{height}', '320x180', $stream->thumbnail_url);
                $twitchChannel->lastUpdated = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $twitchChannel->title = $stream->title;
                $twitchChannel->subtitle = $stream->game_name;
                $twitchChannel->image = $thumbnail;
                $twitchChannel->live = true;
                $twitchChannel->viewers = $stream->viewer_count;

                // If stream doesn't exist make it
                if (!array_key_exists($twitchChannel->getId(), $currentStreamMap)) {
                    $started = new DateTimeImmutable($stream->started_at);
                    $currentStream = new Stream($twitchChannel->getId(), $stream->id, $stream->title, $started);
                    $twitchChannel->lastStream = $started;
                    $this->streamRepository->save($currentStream);
                }

                $this->channelRepository->saveChannel($twitchChannel);
                

            // If the channel is not live on twitch and there was user info
            } elseif (array_key_exists($twitchChannel->name, $offlineUsersMap)) {
                $user = $offlineUsersMap[$twitchChannel->name];
                $twitchChannel->lastUpdated = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $twitchChannel->title = $user->display_name;
                $twitchChannel->subtitle = '';
                $twitchChannel->image = $user->offline_image_url !== '' ? $user->offline_image_url : $user->profile_image_url;
                $twitchChannel->live = false;
                $twitchChannel->viewers = 0;

                // If current stream exists, make it is stopped
                if (array_key_exists($twitchChannel->getId(), $currentStreamMap)) {
                    $currentStream = $currentStreamMap[$twitchChannel->getId()];
                    $currentStream->stoppedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                    $this->streamRepository->save($currentStream);
                }

                $this->channelRepository->saveChannel($twitchChannel);
            }
        }
    }

    /**
     * Get all twitch channels from the database, use the AngelThump API to get updated info, then update the database
     *
     * @throws GuzzleException
     */
    public function updateAngelThumpChannels(): void
    {
        echo "Updating Angel Thump Channels...\n";

        // Get all twitch channels from the database
        $angelThumpChannels = $this->channelRepository->getChannelsForService('angelthump');

        $usernames = [];

        foreach ($angelThumpChannels as $angelThumpChannel) {
            $usernames[] = $angelThumpChannel->name;
        }

        $liveStreamsMap = [];
        $offlineUsersMap = [];

        // Get current streams
        $streams = $this->angelThump->getStreams();

        $liveUsernames = [];

        foreach ($streams as $stream) {
            $liveStreamsMap[$stream->user->username] = $stream;
            $liveUsernames[] = $stream->user->username;
        }

        // For not live channels get the user information
        $offlineUsernames = array_values(array_diff($usernames, $liveUsernames));

        foreach($offlineUsernames as $offlineUsername) {
            $user = $this->angelThump->getUser($offlineUsername);

            if ($user === null) {
                continue;
            }

            $offlineUsersMap[$user->username] = $user;
        }

        $currentStreams = $this->streamRepository->getCurrentStreams();

        /** @var Stream[] */
        $currentStreamMap = [];

        // Create map of channel ID to stream
        foreach ($currentStreams as $currentStream) {
            $currentStreamMap[$currentStream->channelId] = $currentStream;
        }

        // Update database with updated info
        foreach($angelThumpChannels as $angelThumpChannel) {
            echo "Updating database record for $angelThumpChannel->name...\n";

            // If the channel is live on AngelThump
            if (array_key_exists($angelThumpChannel->name, $liveStreamsMap)) {
                $stream = $liveStreamsMap[$angelThumpChannel->name];
                $angelThumpChannel->lastUpdated = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $angelThumpChannel->title = $stream->user->title;
                $angelThumpChannel->subtitle = '';
                $angelThumpChannel->image = $stream->thumbnail_url;
                $angelThumpChannel->live = true;
                $angelThumpChannel->viewers = $stream->viewer_count;

                // If stream doesn't exist make it
                if (!array_key_exists($angelThumpChannel->getId(), $currentStreamMap)) {
                    $started = new DateTimeImmutable($stream->createdAt);
                    $currentStream = new Stream($angelThumpChannel->getId(), $stream->id, $stream->user->title, $started);
                    $angelThumpChannel->lastStream = $started;
                    $this->streamRepository->save($currentStream);
                }

                $this->channelRepository->saveChannel($angelThumpChannel);
                

            // If the channel is not live on AngelTHump and there was user info
            } elseif (array_key_exists($angelThumpChannel->name, $offlineUsersMap)) {
                $user = $offlineUsersMap[$angelThumpChannel->name];
                $angelThumpChannel->lastUpdated = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $angelThumpChannel->title = $user->display_name;
                $angelThumpChannel->subtitle = '';
                $angelThumpChannel->image = $user->offline_banner_url !== '' ? $user->offline_banner_url : $user->profile_logo_url;
                $angelThumpChannel->live = false;
                $angelThumpChannel->viewers = 0;

                // If current stream exists, make it is stopped
                if (array_key_exists($angelThumpChannel->getId(), $currentStreamMap)) {
                    $currentStream = $currentStreamMap[$angelThumpChannel->getId()];
                    $currentStream->stoppedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                    $this->streamRepository->save($currentStream);
                }
                $this->channelRepository->saveChannel($angelThumpChannel);
            }
        }
    }
}