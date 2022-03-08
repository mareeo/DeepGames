<?php


namespace App\Actions;

use League\Plates\Engine;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

class HomeAction
{
    /** @var Engine */
    private $plates;

    public function __construct(Engine $engine)
    {
        $this->plates = $engine;
    }

    public function __invoke(ServerRequest $request, Response $response): Response
    {
        $body = $this->plates->render('index');
        $response->getBody()->write($body);
        return $response;
    }
}