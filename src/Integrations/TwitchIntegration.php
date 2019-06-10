<?php

namespace DeepGamers\Integrations;

use GuzzleHttp\Client;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;

/**
 * Interacts with the "New Twitch API" to get current status of channels.
 * Up to 100 channels may be fetched in a single request from the Twitch API, so not many API requests are needed.
 * Class TwitchIntegration
 * @package DeepGamers\Integrations
 */
class TwitchIntegration
{
    private const STREAM_SCHEMA = <<<'JSON'
{
    "type": "object",
    "properties": {
        "data": {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "community_ids": {
                        "type": "array",
                        "items": {
                            "type": "string"
                          }
                    },
                    "game_id": {
                        "type": "string"
                    },
                    "id": {
                        "type": "string"
                    },
                    "language": {
                        "type": "string"
                    },
                    "started_at": {
                        "type": "string"
                    },
                    "tag_ids": {
                        "type": "array",
                        "items": {
                            "type": "string"
                          }
                    },
                    "thumbnail_url": {
                        "type": "string"
                    },
                    "title": {
                        "type": "string"
                    },
                    "type": {
                        "type": "string"
                    },
                    "user_id": {
                        "type": "string"
                    },
                    "user_name": {
                        "type": "string"
                    },
                    "viewer_count": {
                        "type": "integer"
                    }
                },
                "required": ["community_ids", "game_id", "id", "language", "started_at", "tag_ids", "thumbnail_url", "title", "user_id", "user_name", "viewer_count"]
            }
        }
    },
    "required": ["data"]
}
JSON;

    private const USER_SCHEMA = <<<'JSON'
{
    "type": "object",
    "properties": {
        "data": {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "broadcaster_type": {
                        "type": "string"
                    },
                    "description": {
                        "type": "string"
                    },
                    "display_name": {
                        "type": "string"
                    },
                    "email": {
                        "type": "string"
                    },
                    "id": {
                        "type": "string"
                    },
                    "login": {
                        "type": "string"
                    },
                    "offline_image_url": {
                        "type": "string"
                    },
                    "profile_image_url": {
                        "type": "string"
                    },
                    "type": {
                        "type": "string"
                    },
                    "view_count": {
                        "type": "integer"
                    }
                },
                "required": ["broadcaster_type", "description", "id", "login", "offline_image_url", "profile_image_url", "type", "view_count"]
            }
        }
    },
    "required": ["data"]
}
JSON;

    private const GAME_SCHEMA = <<<'JSON'
{
    "type": "object",
    "properties": {
        "data": {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "id": {
                        "type": "string"
                    },
                    "name": {
                        "type": "string"
                    },
                    "box_art_url": {
                        "type": "string"
                    }
                },
                "required": ["id", "name", "box_art_url"]
            }
        }
    },
    "required": ["data"]
}
JSON;

    /** @var Client */
    private $guzzle;

    /** @var Validator */
    private $validator;

    public function __construct(string $clientID)
    {
        $this->guzzle = new Client([
            'base_uri' => 'https://api.twitch.tv/helix/',
            'headers' => [
                'Client-ID' => $clientID
            ]
        ]);

        $this->validator = new Validator();
    }

    /**
     * Get channel information for Twitch channels.
     * First, all live streams are fetched. Then for offline streams the channel information (technically user
     * information) is fetched. Finally, game information is fetched.
     * @param array $channels
     * @return StreamInfo[]
     * @throws \Exception
     */
    public function getStreamInfo(array $channels)
    {
        // Get information for live and not live channels and merge the results together
        $liveStreams = $this->getLiveStreams($channels);
        $offlineUsers = array_values(array_diff($channels, array_keys($liveStreams)));
        $offlineStreams = $this->getUsersInfo($offlineUsers);

        /** @var StreamInfo[] $allStreams */
        $allStreams = array_merge($liveStreams, $offlineStreams);

        // Fetch and add game information to the data
        $this->addGameInformation($allStreams);

        return $allStreams;
    }

    /**
     * Get current stream information for any live streams from the Twitch API.
     * @param array $channels
     * @return StreamInfo[] A map where keys are usernames and values are StreamInfo objects.
     * @throws \Exception
     */
    private function getLiveStreams(array $channels): array
    {
        if (count($channels) == 0) {
            return [];
        }

        // Do the API request
        $response = $this->guzzle->get('streams', [
            'query' => ['user_login' => $channels ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception($response->getStatusCode(), $response->getReasonPhrase());
        }


        $body = json_decode($response->getBody()->getContents());
        $result = $this->validator->schemaValidation($body, Schema::fromJsonString(self::STREAM_SCHEMA));

        if (!$result->isValid()) {
            $error = $result->getFirstError();
            $message = "Validation '" . $error->keyword() . "' error on " . implode('.', $error->dataPointer()) . ": ";
            $message .= json_encode($error->keywordArgs());
            throw new \Exception("Invalid Twitch API response: $message");
        }

        // Build StreamInfo map
        $infoMap = [];
        foreach ($body->data as $streamObject) {
            $infoMap[$streamObject->user_name] = $this->makeFromStream($streamObject);
        }

        return $infoMap;

    }

    /**
     * Make a StreamInfo object from the live stream Twitch API response.
     * @param \stdClass $streamObject
     * @return StreamInfo
     */
    private function makeFromStream(\stdClass $streamObject): StreamInfo
    {
        $thumbnail = str_replace('{width}x{height}', '320x180', $streamObject->thumbnail_url);
        $streamInfo = new StreamInfo(
            $streamObject->user_name,
            'twitch',
            true,
            $thumbnail,
            $streamObject->title,
            $streamObject->viewer_count
        );

        $streamInfo->twitchGameId = $streamObject->game_id;
        return $streamInfo;
    }

    /**
     * Get user (channel) information from the Twitch API.
     * Only needed for offline channels.
     * @param array $channels
     * @return StreamInfo[] A map where keys are usernames and values are StreamInfo objects.
     * @throws \Exception
     */
    private function getUsersInfo(array $channels): array
    {
        if (count($channels) == 0) {
            return [];
        }

        // Do the API request
        $response = $this->guzzle->get('users', [
            'query' => ['login' => $channels ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception($response->getStatusCode(), $response->getReasonPhrase());
        }

        // Validate the result
        $body = json_decode($response->getBody()->getContents());
        $result = $this->validator->schemaValidation($body, Schema::fromJsonString(self::USER_SCHEMA));

        if (!$result->isValid()) {
            $error = $result->getFirstError();
            $message = "Validation '" . $error->keyword() . "' error on " . implode('.', $error->dataPointer()) . ": ";
            $message .= json_encode($error->keywordArgs());
            throw new \Exception("Invalid Twitch API response: $message");
        }

        // Build StreamInfo map
        $infoMap = [];
        foreach ($body->data as $streamObject) {
            $infoMap[$streamObject->login] = $this->makeFromUser($streamObject);
        }

        return $infoMap;
    }

    /**
     * Make a StreamInfo object from the user Twitch API response.
     * @param \stdClass $userObject
     * @return StreamInfo
     */
    private function makeFromUser(\stdClass $userObject): StreamInfo
    {
        return new StreamInfo(
            $userObject->login,
            'twitch',
            false,
            $userObject->offline_image_url !== '' ?  $userObject->offline_image_url : $userObject->profile_image_url,
            $userObject->display_name,
            0
        );
    }

    /**
     * Fetches game information (name, boxart) for stream from the Twitch API and
     * update the StreamInfo data structures with the game name.
     * @param StreamInfo[] $streams
     * @throws \Exception
     */
    private function addGameInformation(array $streams)
    {
        // Get game IDs of live streams
        $gameIds = [];
        foreach($streams as $stream) {
            if ($stream->twitchGameId !== null) {
                $gameIds[] = $stream->twitchGameId;
            }
        }

        // Remove duplicates
        $gameIds = array_values(array_unique($gameIds));

        // Fetch game information from Twitch API
        $gamesMap = $this->getGames($gameIds);

        // Game game names to the StreamInfo array
        foreach($streams as $stream) {
            if(isset($gamesMap[$stream->twitchGameId])) {
                $stream->game = $gamesMap[$stream->twitchGameId]->name;
            }
        }
    }

    /**
     * Get game information from the Twitch API.
     * @param array $gameIds
     * @return array A map where values are Twitch game IDs and values keyed arrays: ['box_art_url', 'id', 'name']
     * @throws \Exception
     */
    private function getGames(array $gameIds): array
    {
        if (count($gameIds) == 0) {
            return [];
        }

        // Do the API request
        $response = $this->guzzle->get('games', [
            'query' => ['id' => $gameIds ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception($response->getStatusCode(), $response->getReasonPhrase());
        }

        // Validate the response
        $body = json_decode($response->getBody()->getContents());
        $result = $this->validator->schemaValidation($body, Schema::fromJsonString(self::GAME_SCHEMA));

        if (!$result->isValid()) {
            $error = $result->getFirstError();
            $message = "Validation '" . $error->keyword() . "' error on " . implode('.', $error->dataPointer()) . ": ";
            $message .= json_encode($error->keywordArgs());
            throw new \Exception("Invalid Twitch API response: $message");
        }

        // Build game information map
        $output = [];
        foreach ($body->data as $game) {
            $output[$game->id] = $game;
        }

        return $output;
    }
}