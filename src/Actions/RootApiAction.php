<?php


namespace App\Actions;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

class RootApiAction
{
    public function __invoke(ServerRequest $request, Response $response, $args): Response
    {
        $payload = json_encode([
            'channels' => '/channels',
        ]);

        $response->getBody()->write($payload);

        $response = $response->withHeader('Content-Type', 'application/json');

        return $response;
    }
}