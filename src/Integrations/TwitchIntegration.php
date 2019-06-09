<?php

namespace DeepGamers\Integrations;

use GuzzleHttp\Client;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;

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

    public function getStreamInfo(array $channels)
    {
        $liveStreams = $this->getLiveStreams($channels);
        $offlineUsers = array_values(array_diff($channels, array_keys($liveStreams)));
        $offlineStreams = $this->getUsersInfo($offlineUsers);

        return array_merge($liveStreams, $offlineStreams);
    }

    /**
     * @param array $channels
     * @return StreamInfo[]
     * @throws \Exception
     */
    private function getLiveStreams(array $channels): array
    {
        $response = $this->guzzle->get("streams", [
            'query' => ['user_login' => $channels ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception($response->getStatusCode(), $response->getReasonPhrase());
        }

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents);

        $result = $this->validator->schemaValidation($body, Schema::fromJsonString(self::STREAM_SCHEMA));

        if (!$result->isValid()) {
            $error = $result->getFirstError();
            $message = "Validation '" . $error->keyword() . "' error on " . implode('.', $error->dataPointer()) . ": ";
            $message .= json_encode($error->keywordArgs());
            throw new \Exception("Invalid Twitch API response: $message");
        }

        $infoMap = [];

        foreach ($body->data as $streamObject) {
            $infoMap[$streamObject->user_name] = $this->makeFromStream($streamObject);
        }

        return $infoMap;

    }

    private function makeFromStream(\stdClass $streamObject): StreamInfo
    {
        $thumbnail = str_replace('{width}x{height}', '320x180', $streamObject->thumbnail_url);
        return new StreamInfo(
            $streamObject->user_name,
            'twitch',
            true,
            $thumbnail,
            $streamObject->title,
            $streamObject->viewer_count
        );
    }

    /**
     * @param array $channels
     * @return StreamInfo[]
     * @throws \Exception
     */
    private function getUsersInfo(array $channels): array
    {
        $response = $this->guzzle->get("users", [
            'query' => ['login' => $channels ],

        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception($response->getStatusCode(), $response->getReasonPhrase());
        }

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents);

        $result = $this->validator->schemaValidation($body, Schema::fromJsonString(self::USER_SCHEMA));

        if (!$result->isValid()) {
            $error = $result->getFirstError();
            $message = "Validation '" . $error->keyword() . "' error on " . implode('.', $error->dataPointer()) . ": ";
            $message .= json_encode($error->keywordArgs());
            throw new \Exception("Invalid Twitch API response: $message");
        }

        $infoMap = [];

        foreach ($body->data as $streamObject) {
            $infoMap[$streamObject->login] = $this->makeFromUser($streamObject);
        }

        return $infoMap;
    }

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
}