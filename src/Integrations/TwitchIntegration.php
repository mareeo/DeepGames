<?php

namespace DeepGamers\Integrations;

use GuzzleHttp\Client;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Interacts with the "New Twitch API" to get current status of channels.
 * Up to 100 channels may be fetched in a single request from the Twitch API, so not many API requests are needed.
 * Class TwitchIntegration
 * @package DeepGamers\Integrations
 */
class TwitchIntegration
{
    private const OAUTH_TOKEN_SCHEMA = <<<'JSON'
{
    "type": "object",
    "properties": {
        "access_token": {
            "type": "string"
        },
        "refresh_token": {
            "type": "string"
        },
        "expires_in": {
            "type": "integer"
        },
        "scope": {
            "type": "array"
        },
        "token_type": {
            "type": "string"
        }
    },
    "required": ["access_token", "expires_in", "token_type"]
}
JSON;

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
                "required": ["game_id", "id", "language", "started_at", "tag_ids", "thumbnail_url", "title", "user_id", "user_name", "viewer_count"]
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

    /** @var CacheInterface */
    private $cache;

    /** @var string */
    private $accessTokenCacheKey;

    /** @var Validator */
    private $validator;

    /** @var string */
    private $clientID;

    /** @var string */
    private $clientSecret;

    /** @var string */
    private $accessToken;

    /**
     * TwitchIntegration constructor.
     * @param CacheInterface $cache
     * @param string $clientID
     * @param string $clientSecret
     * @param string $accessTokenCacheKey
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function __construct(CacheInterface $cache, string $clientID, string $clientSecret, string $accessTokenCacheKey)
    {
        $this->cache = $cache;
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->accessTokenCacheKey = $accessTokenCacheKey;

        $this->guzzle = new Client([
            'base_uri' => 'https://api.twitch.tv/helix/',
            'headers' => [
                'Client-ID' => $clientID
            ]
        ]);

        $this->validator = new Validator();

        $accessToken = $cache->get($this->accessTokenCacheKey);

        if ($accessToken !== null) {
            $this->setAccessToken($accessToken);
        } else {
            $this->updateOAuthAccessToken();
        }
    }

    /**
     * Get channel information for Twitch channels.
     * First, all live streams are fetched. Then for offline streams the channel information (technically user
     * information) is fetched. Finally, game information is fetched.
     * @param array $channels
     * @return StreamInfo[]
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getStreamInfo(array $channels): array
    {
        /** @var StreamInfo[] $allStreams */
        $allStreams = [];

        // Twitch API calls allow a max of 100 streams per request
        foreach(array_chunk($channels, 100) as $channelsChunk) {

            // Get information for live and not live channels and merge the results together
            $liveStreams = $this->getLiveStreams($channels);
            $offlineUsers = array_values(array_diff($channelsChunk, array_keys($liveStreams)));
            $offlineStreams = $this->getUsersInfo($offlineUsers);

            /** @var StreamInfo[] $chunkAllStreams */
            $chunkAllStreams = array_merge($liveStreams, $offlineStreams);

            // Fetch and add game information to the data
            $this->addGameInformation($chunkAllStreams);

            // All this chunk to the full list
            $allStreams = array_merge($allStreams, $chunkAllStreams);
        }

        return $allStreams;
    }

    private function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
        $this->guzzle = new Client([
            'base_uri' => 'https://api.twitch.tv/helix/',
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}"
            ]
        ]);
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    private function updateOAuthAccessToken(): void
    {
        $response = $this->guzzle->post('https://id.twitch.tv/oauth2/token', [
            'http_errors' => false,
            'query' => [
                'client_id' => $this->clientID,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials'
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception($response->getStatusCode(), $response->getReasonPhrase());
        }

        $body = json_decode($response->getBody()->getContents());
        $result = $this->validator->schemaValidation($body, Schema::fromJsonString(self::OAUTH_TOKEN_SCHEMA));

        if (!$result->isValid()) {
            $error = $result->getFirstError();
            $message = "Validation '" . $error->keyword() . "' error on " . implode('.', $error->dataPointer()) . ": ";
            $message .= json_encode($error->keywordArgs());
            throw new \Exception("Invalid Twitch API response: $message");
        }

        $this->setAccessToken($body->access_token);
        $this->cache->set($this->accessTokenCacheKey, $body->access_token,  $body->expires_in);
    }

    /**
     * Get current stream information for any live streams from the Twitch API.
     * @param array $channels
     * @return StreamInfo[] A map where keys are usernames and values are StreamInfo objects.
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function getLiveStreams(array $channels): array
    {
        if (count($channels) == 0) {
            return [];
        }

        // Do the API request
        $response = $this->guzzle->request('GET', 'streams', [
            'http_errors' => false,
            'query' => ['user_login' => $channels ]
        ]);

        $this->checkErrorResponse($response);

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
            $infoMap[strtolower($streamObject->user_name)] = $this->makeFromStream($streamObject);
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function getUsersInfo(array $channels): array
    {
        if (count($channels) == 0) {
            return [];
        }

        // Do the API request
        $response = $this->guzzle->get('users', [
            'http_errors' => false,
            'query' => ['login' => $channels ]
        ]);

        $this->checkErrorResponse($response);

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
            $infoMap[strtolower($streamObject->login)] = $this->makeFromUser($streamObject);
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
    private function addGameInformation(array $streams): void
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
            'http_errors' => false,
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

    /**
     * @param ResponseInterface $response
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    private function checkErrorResponse(ResponseInterface $response): void
    {
        // If not authorized clear the cached access token
        if ($response->getStatusCode() === 401) {
            $this->cache->delete($this->accessTokenCacheKey);
        }

        if ($response->getStatusCode() >= 300) {
            throw new \Exception('Twitch API Error: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase() . ' ' . $response->getBody(), $response->getStatusCode());
        }
    }
}