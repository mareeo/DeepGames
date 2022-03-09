<?php


namespace App\Integrations\AngelThump;

use App\DB\Stream;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use stdClass;

class AngelThumpApi
{
    private const STREAM_SCHEMA = /** @lang JSON */
        <<<'JSON'
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

    private const USER_SCHEMA = /** @lang JSON */
        <<<'JSON'
{
    "type": "array",
    "items": {
        "type": "object",
        "properties": {
            "username": {
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

    private Validator $validator;

    public function __construct()
    {
        $this->guzzle = new Client([
            'base_uri' => 'https://api.angelthump.com/v3/'
        ]);

        $this->validator = new Validator();
    }

    /**
     * @param string $username
     * @return ?Stream
     * @throws Exception
     */
    public function getStreamInfo(string $username): ?Stream
    {
        $response = $this->guzzle->get("streams?username=$username");

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents);

        $result = $this->validator->schemaValidation($body, Schema::fromJsonString(self::STREAM_SCHEMA));

        if (!$result->isValid()) {
            $error = $result->getFirstError();
            $message = "Validation '" . $error->keyword() . "' error on " . implode('.', $error->dataPointer()) . ": ";
            $message .= json_encode($error->keywordArgs());
            throw new Exception("Invalid AngelThump API response: $message");
        }

        if (count($body) === 0) {
            return null;
        }

        $streamObject = $body[0];

        return new Stream(
            $streamObject->user->username,
            'angelthump',
            true,
            $streamObject->thumbnail_url,
            $streamObject->user->title,
            $streamObject->viewer_count
        );
    }

    public function getUserInfo(string $username): ?Stream
    {
        $response = $this->guzzle->get("users?username=$username");

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents);

        $result = $this->validator->schemaValidation($body, Schema::fromJsonString(self::USER_SCHEMA));

        if (!$result->isValid()) {
            $error = $result->getFirstError();
            $message = "Validation '" . $error->keyword() . "' error on " . implode('.', $error->dataPointer()) . ": ";
            $message .= json_encode($error->keywordArgs());
            throw new Exception("Invalid AngelThump API response: $message");
        }

        if (count($body) === 0) {
            return null;
        }

        $userObject = $body[0];

        return new Stream(
            $userObject->username,
            'angelthump',
            false,
            $userObject->profile_logo_url,
            $userObject->title,
            0
        );
    }

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