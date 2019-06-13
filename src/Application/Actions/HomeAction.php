<?php


namespace DeepGamers\Application\Actions;

use DI\Container;
use League\Plates\Engine;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

class HomeAction
{
    /** @var Container */
    private $container;

    /** @var Engine */
    private $plates;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->plates = $this->container->get(Engine::class);
    }

    public function __invoke(ServerRequest $request, Response $response): Response
    {

        $body = $this->plates->render('index.phtml');
        $response->getBody()->write($body);
        return $response;
    }
}