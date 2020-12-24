<?php


namespace DeepGamers\Application\Actions;


use DeepGamers\ImgDump;
use DI\Container;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PDO;

class ImgDumpRemoveAction
{
    /** @var Container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke(ServerRequest $request, Response $response, $args): Response
    {
        $dbh = $this->container->get(PDO::class);
        $imgDump = new ImgDump($dbh);
        return $imgDump->removeImage($request, $response);
    }
}