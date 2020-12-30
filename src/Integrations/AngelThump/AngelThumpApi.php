<?php


namespace App\Integrations\AngelThump;

use App\DB\Stream;
use Exception;
use GuzzleHttp\Client;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use stdClass;

class AngelThumpApi
{
    private const USER_SCHEMA = /** @lang JSON */
        <<<'JSON'
{
    "type": "object",
    "properties": {
        "username": {
            "type": "string"
        },
        "type": {
            "type": "string"
        },
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
                },
                "profile_logo_url": {
                  "type": "string"
                }
            },
            "required": ["username", "title", "profile_logo_url"]
        }
    },
    "required": ["username", "type"]
}
JSON;

    private Client $guzzle;

    private Validator $validator;

    public function __construct()
    {
        $this->guzzle = new Client([
            'base_uri' => 'https://api.angelthump.com/v2/'
        ]);

        $this->validator = new Validator();
    }

    /**
     * @param string $username
     * @return Stream
     * @throws Exception
     */
    public function getStreamInfo(string $username): Stream
    {
        $response = $this->guzzle->get("streams/$username");

        if ($response->getStatusCode() !== 200) {
            throw new Exception($response->getStatusCode(), $response->getReasonPhrase());
        }

        $contents = $response->getBody()->getContents();
        $body = json_decode($contents);

        $result = $this->validator->schemaValidation($body, Schema::fromJsonString(self::USER_SCHEMA));

        if (!$result->isValid()) {
            $error = $result->getFirstError();
            $message = "Validation '" . $error->keyword() . "' error on " . implode('.', $error->dataPointer()) . ": ";
            $message .= json_encode($error->keywordArgs());
            throw new Exception("Invalid AngelThump API response: $message");
        }

        return $this->makeStreamInfo($body);
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