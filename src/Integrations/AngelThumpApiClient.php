<?php


namespace App\Integrations;

use App\DB\Stream;
use Exception;
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
                    "thumbnail_url": {
                        "type": "string"
                    },
                    "viewer_count": {
                        "type": "integer"
                    },
                    "user": {
                        "type": "object",
                        "properties": {
                            "username": {
                                "type": "string"
                            },
                            "title": {
                                "type": "string"
                            }
                        },
                        "required": ["username", "title"]
                    }
                },
                "required": ["thumbnail_url", "viewer_count", "user"]
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
                    "usernameee": {
                        "type": "string"
                    },
                    "title": {
                        "type": "string"
                    },
                    "profile_logo_url": {
                        "type": "string"
                    }
                },
                "required": ["username", "title", "profile_logo_url"]
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
      * Get user details.
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

          var_dump($body);

          $this->validateResponse($body, self::USER_SCHEMA);
  
          if (count($body) > 0) {
              return $body[0];
          } else {
              return null;
          }
      }

      public function getStreams()
      {
        $response = $this->guzzle->get("streams");

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents);

        $this->validateResponse($body, self::STREAM_SCHEMA);

        return $body;
      }

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
     * Valids the data with the given JSON Schema and throws an exception if invalid.
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

        $errors = $validator->getErrors();
  
        $errorMessage = 'Validation error:';
        foreach($validator->getErrors() as $error) {
            $errorMessage .= ' ' . $error['property'] . ' ' . $error['message'];
        }

        throw new RuntimeException($errorMessage);
    }

    /**
     * Undocumented function
     *
     * @param string $username
     * @return void
     */

     

    private function makeStreamInfo(stdClass $streamObject): Stream
    {
        if ($streamObject->type === "live") {
            $live = true;
            $viewers = $streamObject->viewer_count;
            $thumbnail = $streamObject->thumbnail_url;
        } else {
            $live = false;
            $viewers = 0;
            $thumbnail = $streamObject->user->profile_logo_url;
        }
        return new Stream(
            $streamObject->user->username,
            'angelthump',
            $live,
            $thumbnail,
            $streamObject->user->title,
            $viewers
        );
    }



}