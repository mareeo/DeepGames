<?php


namespace DeepGamers\Application\Actions;

use DeepGamers\ImgDump;
use League\Plates\Engine;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

class ImgDumpPageAction
{
    /** @var Engine */
    private $plates;

    /** @var ImgDump */
    private $imgDump;

    public function __construct(Engine $engine, ImgDump $imgDump)
    {
        $this->plates = $engine;
        $this->imgDump = $imgDump;
    }

    public function __invoke(ServerRequest $request, Response $response, $args): Response
    {
        $body = $this->plates->render('imgdump.phtml', ['imgDump' => $this->imgDump]);
        $response->getBody()->write($body);
        return $response;
    }
}