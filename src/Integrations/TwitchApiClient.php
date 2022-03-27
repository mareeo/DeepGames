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
                            "id": {
                                "type": "string"
                            },
                            "user_login": {
                                "type": "string"
                            },
                            "game_name": {
                                "type": "string"
                            },
                            "title": {
                                "type": "string"
                            },
                            "viewer_count": {
                                "type": "integer"
                            },
                            "started_at": {
                                "type": "string"
                            },
                            "thumbnail_url": {
                                "type": "string"
                            }
                            
                        },
                        "required": ["id", "user_login", "game_name", "title", "viewer_count", "started_at", "thumbnail_url"]
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
                            "display_name": {
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
                            }
                        },
                        "required": ["display_name", "login", "offline_image_url", "profile_image_url"]
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

        $usernames = array_values(array_unique($usernames));

        $output = [];

        // Twitch API calls allow a max of 100 streams per request
        foreach(array_chunk($usernames, 100) as $usernamesChunk) {

            // Do the API request
            $response = $this->guzzle->get('users', [
                'http_errors' => false,
                'query' => ['login' => $usernamesChunk]
            ]);

            $this->checkErrorResponse($response);

            // Validate the result
            $body = json_decode($response->getBody()->getContents());

            $this->validateResponse($body, self::USER_SCHEMA);

            $output = array_merge($output, $body->data);
        }

        return $output;
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

        $usernames = array_values(array_unique($usernames));

        $output = [];

        // Twitch API calls allow a max of 100 streams per request
        foreach(array_chunk($usernames, 100) as $usernamesChunk) {
            // Do the API request
            $response = $this->guzzle->request('GET', 'streams', [
                'http_errors' => false,
                'query' => ['user_login' => $usernamesChunk]
            ]);

            $this->checkErrorResponse($response);

            $body = json_decode($response->getBody()->getContents());

            $this->validateResponse($body, self::STREAM_SCHEMA);

            $output = array_merge($output, $body->data);
        }

        return $output;

        
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