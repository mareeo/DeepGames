<?php

namespace App\Integrations;

use App\DB\Stream;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;
use stdClass;

/**
 * Interacts with the "New Twitch API" to get current status of users$usernames.
 * Up to 100 users$usernames may be fetched in a single request from the Twitch API, so not many API requests are needed.
 * Class TwitchApi
 * @package App\Integrations
 */
class TwitchApiClient
{
    private const API_BASE_URL = 'https://api.twitch.tv/helix/';
    private const OAUTH_URL = 'https://id.twitch.tv/oauth2/token';

    private const OAUTH_TOKEN_SCHEMA =
        <<<JSON
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

    private const STREAM_SCHEMA =
        <<<JSON
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
                            "game_name": {
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

    private const USER_SCHEMA = 
        <<<JSON
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

    private const GAME_SCHEMA = 
        <<<JSON
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
    
    private Client $guzzle;
    private CacheInterface $cache;
    private string $accessTokenCacheKey;
    private string $clientID;
    private string $clientSecret;
    private string $accessToken;

    /**
     * TwitchIntegration constructor.
     * @param CacheInterface $cache
     * @param string $clientID
     * @param string $clientSecret
     * @param string $accessTokenCacheKey
     * @throws InvalidArgumentException
     */
    public function __construct(CacheInterface $cache, string $clientID, string $clientSecret, string $accessTokenCacheKey)
    {
        $this->cache = $cache;
        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->accessTokenCacheKey = $accessTokenCacheKey;

        $this->guzzle = new Client([
            'base_uri' => self::API_BASE_URL,
            'headers' => [
                'Client-ID' => $clientID
            ]
        ]);

        $accessToken = $cache->get($this->accessTokenCacheKey);

        if ($accessToken !== null) {
            $this->setAccessToken($accessToken);
        } else {
            $this->updateOAuthAccessToken();
        }
    }

    /**
     * Get users information
     *
     * @param array $usernames
     * @return array Array of user objects. See user schema constant for fields.
     */
    public function getUsers(array $usernames): array
    {
        if (count($usernames) == 0) {
            return [];
        }

        // Do the API request
        $response = $this->guzzle->get('users', [
            'http_errors' => false,
            'query' => ['login' => $usernames ]
        ]);

        $this->checkErrorResponse($response);

        // Validate the result
        $body = json_decode($response->getBody()->getContents());

        $this->validateResponse($body, self::USER_SCHEMA);

        return $body->data;
    }

    /**
     * Get current stream information for any live streams from the Twitch API.
     * @param array $usernames
     * @return array A map where keys are usernames and values are StreamInfo objects.
     * @throws Exception
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function getStreams(array $usernames): array
    {
        if (count($usernames) == 0) {
            return [];
        }

        // Do the API request
        $response = $this->guzzle->request('GET', 'streams', [
            'http_errors' => false,
            'query' => ['user_login' => $usernames ]
        ]);

        $this->checkErrorResponse($response);

        $body = json_decode($response->getBody()->getContents());

        $this->validateResponse($body, self::STREAM_SCHEMA);

        return $body->data;
    }

    /**
     * Get game information from the Twitch API.
     * 
     * @param array $gameIds
     * @return array A map where values are Twitch game IDs and values keyed arrays: ['box_art_url', 'id', 'name']
     * @throws Exception
     */
    public function getGames(array $gameIds): array
    {
        if (count($gameIds) == 0) {
            return [];
        }

        // Do the API request
        $response = $this->guzzle->get('games', [
            'http_errors' => false,
            'query' => ['id' => $gameIds ]
        ]);

        $this->checkErrorResponse($response);

        // Validate the response
        $body = json_decode($response->getBody()->getContents());
        $this->validateResponse($body, self::GAME_SCHEMA);

        // Build game information map
        $output = [];
        foreach ($body->data as $game) {
            $output[$game->id] = $game;
        }

        return $output;
    }

    /**
     * Get channel information for Twitch users$usernames.
     * First, all live streams are fetched. Then for offline streams the channel information (technically user
     * information) is fetched. Finally, game information is fetched.
     * @param array $usernames
     * @return Stream[]
     * @throws Exception
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function getStreamInfo(array $usernames): array
    {
        /** @var Stream[] $allStreams */
        $allStreams = [];

        // Twitch API calls allow a max of 100 streams per request
        foreach(array_chunk($usernames, 100) as $channelsChunk) {

            // Get information for live and not live users$usernames and merge the results together
            $liveStreams = $this->getLiveStreams($usernames);
            $offlineUsers = array_values(array_diff($channelsChunk, array_keys($liveStreams)));
            $offlineStreams = $this->getUsersInfo($offlineUsers);

            /** @var Stream[] $chunkAllStreams */
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
            'base_uri' => self::API_BASE_URL,
            'headers' => [
                'Client-ID' => $this->clientID,
                'Authorization' => "Bearer {$this->accessToken}"
            ]
        ]);
    }

    /**
     * @throws RuntimeException
     */
    private function updateOAuthAccessToken(): void
    {
        $response = $this->guzzle->post(self::OAUTH_URL, [
            'http_errors' => false,
            'query' => [
                'client_id' => $this->clientID,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials'
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException($response->getReasonPhrase(), $response->getStatusCode());
        }

        $body = json_decode($response->getBody()->getContents());

        $this->validateResponse($body, self::OAUTH_TOKEN_SCHEMA);

        $this->setAccessToken($body->access_token);
        $this->cache->set($this->accessTokenCacheKey, $body->access_token,  $body->expires_in);
    }

    

    /**
     * Make a StreamInfo object from the live stream Twitch API response.
     * @param stdClass $streamObject
     * @return Stream
     */
    private function makeFromStream(stdClass $streamObject): Stream
    {
        $thumbnail = str_replace('{width}x{height}', '320x180', $streamObject->thumbnail_url);
        $streamInfo = new Stream(
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
     * Get user information from the Twitch API.

     * @param array $usernames
     * @return Stream[] A map where keys are usernames and values are StreamInfo objects.
     * @throws Exception
     * @throws InvalidArgumentException
     */
    private function getUsersInfo(array $usernames): array
    {
        if (count($usernames) == 0) {
            return [];
        }

        // Do the API request
        $response = $this->guzzle->get('users', [
            'http_errors' => false,
            'query' => ['login' => $usernames ]
        ]);

        $this->checkErrorResponse($response);

        // Validate the result
        $body = json_decode($response->getBody()->getContents());
        $result = $this->validator->schemaValidation($body, Schema::fromJsonString(self::USER_SCHEMA));

        if (!$result->isValid()) {
            $error = $result->getFirstError();
            $message = "Validation '" . $error->keyword() . "' error on " . implode('.', $error->dataPointer()) . ": ";
            $message .= json_encode($error->keywordArgs());
            throw new Exception("Invalid Twitch API response: $message");
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
     * @param stdClass $userObject
     * @return Stream
     */
    private function makeFromUser(stdClass $userObject): Stream
    {
        return new Stream(
            $userObject->login,
            'twitch',
            false,
            $userObject->offline_image_url !== '' ?  $userObject->offline_image_url : $userObject->profile_image_url,
            $userObject->display_name,
            0
        );
    }

    /**
     * Checks response for a successful resposne code.
     * @param ResponseInterface $response
     * @throws RuntimeException
     */
    private function checkErrorResponse(ResponseInterface $response): void
    {
        // If not authorized clear the cached access token
        if ($response->getStatusCode() === 401) {
            $this->cache->delete($this->accessTokenCacheKey);
        }

        if ($response->getStatusCode() >= 300) {
            throw new RuntimeException('Twitch API Error: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase() . ' ' . $response->getBody(), $response->getStatusCode());
        }
    }

    /**
     * Validates the data with the given JSON Schema and throws an exception if invalid.
     *
     * @param Validator $validator
     * @throws RunTimeException
     * @return void
     */
    private function validateResponse(mixed $data, string $jsonSchema): void
    {
        $validator = new Validator();
        $validator->validate($data, json_decode($jsonSchema));

        if ($validator->isValid()) {
            return;
        }
  
        $errorMessage = 'Validation error:';
        foreach($validator->getErrors() as $error) {
            $errorMessage .= ' ' . $error['property'] . ' ' . $error['message'];
        }

        throw new RuntimeException($errorMessage);
    }
}