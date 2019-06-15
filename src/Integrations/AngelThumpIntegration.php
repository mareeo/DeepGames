<?php


namespace DeepGamers\Integrations;


use GuzzleHttp\Client;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;

class AngelThumpIntegration
{
    private const USER_SCHEMA = <<<'JSON'
{
    "type": "object",
    "properties": {
        "username": {
            "type": "string"
        },
        "poster": {
            "type": "string"
        },
        "title": {
            "type": "string"
        },
        "live": {
            "type": "boolean"
        },
        "thumbnail": {
            "type": "string"
        },
        "viewers": {
            "type": "integer"
        }
    },
    "required": ["username", "poster"]
}
JSON;

    /** @var Client */
    private $guzzle;

    /** @var Validator */
    private $validator;

    public function __construct()
    {
        $this->guzzle = new Client([
            'base_uri' => 'https://api.angelthump.com/v1/'
        ]);

        $this->validator = new Validator();
    }

    /**
     * @param string $username
     * @return StreamInfo
     * @throws \Exception
     */
    public function getStreamInfo(string $username): StreamInfo
    {
        $response = $this->guzzle->get($username);

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
            throw new \Exception("Invalid AngelThump API response: $message");
        }

        return $this->makeStreamInfo($body);
    }

    private function makeStreamInfo(\stdClass $userObject): StreamInfo
    {
        return new StreamInfo(
            $userObject->username,
            'angelthump',
            $userObject->live ?? false,
            $userObject->thumbnail ?? $userObject->poster,
            $userObject->title ?? '',
            $userObject->viewers ?? 0
        );
    }

}