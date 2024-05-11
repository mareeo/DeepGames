<?php


namespace App\Integrations;

use GuzzleHttp\Client;

use JsonSchema\Validator;
use RuntimeException;
use stdClass;

class AngelThumpApiClient
{
    private const API_BASE_URL = 'https://api.angelthump.com/v3/';
    private const STREAM_SCHEMA =
        <<<JSON
        {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "id": {
                        "type": "string"
                    },
                    "thumbnail_url": {
                        "type": "string"
                    },
                    "viewer_count": {
                        "type": "string"
                    },
                    "createdAt": {
                        "type": "string"
                    },
                    "user": {
                        "type": "object",
                        "properties": {
                            "username": {
                                "type": "string"
                            },
                            "display_name": {
                                "type": "string"
                            },
                            "title": {
                                "type": "string"
                            },
                            "offline_banner_url": {
                                "type": "string"
                            },
                            "profile_logo_url": {
                                "type": "string"
                            }
                        },
                        "required": ["username", "display_name", "title", "offline_banner_url", "profile_logo_url"]
                    }
                },
                "required": ["id", "thumbnail_url", "viewer_count", "createdAt", "user"]
            }
        }
        JSON;

    private const USER_SCHEMA =
        <<<JSON
        {
            "type": "array",
            "items": {
                "type": "object",
                "properties": {
                    "username": {
                        "type": "string"
                    },
                    "display_name": {
                        "type": "string"
                    },
                    "title": {
                        "type": "string"
                    },
                    "offline_banner_url": {
                        "type": "string"
                    },
                    "profile_logo_url": {
                        "type": "string"
                    }
                },
                "required": ["username", "display_name", "title", "offline_banner_url", "profile_logo_url"]
            }
        }
        JSON;

    private Client $guzzle;

    public function __construct()
    {
        $this->guzzle = new Client([
            'base_uri' => self::API_BASE_URL
        ]);
    }

    /**
      * Get user information.
      *
      * @param string $username
      * @throws RuntimeException
      * @return stdClass|null User object if found, null if no user found
      */
    public function getUser(string $username): ?stdClass
    {
        $response = $this->guzzle->get("users?username=$username");

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents);

        $this->validateResponse($body, self::USER_SCHEMA);

        if (count($body) > 0) {
            return $body[0];
        } else {
            return null;
        }
    }

    /**
     * Get all stream information.
     * Useful if info about multiple streams is needed
     *
     * @throws RuntimeException
     * @return array Array of stream objects. See stream schema constant for fields.
     */
    public function getStreams(): array
    {
        $response = $this->guzzle->get("streams");

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents);

        $this->validateResponse($body, self::STREAM_SCHEMA);

        return $body;
    }

    /**
     * Get information about a single stream.
     *
     * @param string $username
     * @return stdClass|null Stream object (see stream schema constant for fields). Null if not found.
     */
    public function getStream(string $username): ?stdClass
    {
        $response = $this->guzzle->get("streams?username=$username");

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents);

        $this->validateResponse($body, self::STREAM_SCHEMA);

        if (count($body) > 0) {
            return $body[0];
        } else {
            return null;
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