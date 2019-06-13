<?php


namespace DeepGamers\Application\Actions;

use DeepGamers\ImgDump;
use DI\Container;
use League\Plates\Engine;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

class ImgDumpPageAction
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

    public function __invoke(ServerRequest $request, Response $response, $args): Response
    {
        /** @var \PDO $dbh */
        $dbh = $this->container->get('dbh');

        $imgDump = new ImgDump($dbh);

        $body = $this->plates->render('imgdump.phtml', ['imgDump' => $imgDump]);
        $response->getBody()->write($body);
        return $response;
    }
}