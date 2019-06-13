<?php


namespace DeepGamers\Application\Actions;


use DI\Container;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

class RootApiAction
{
    /** @var Container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

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