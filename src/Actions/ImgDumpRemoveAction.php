<?php


namespace App\Actions;

use DeepGamers\ImgDump;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

class ImgDumpRemoveAction
{
    /** @var ImgDump */
    private $imgDump;

    public function __construct(ImgDump $imgDump)
    {
        $this->imgDump = $imgDump;
    }

    public function __invoke(ServerRequest $request, Response $response, $args): Response
    {
        return $this->imgDump->removeImage($request, $response);
    }
}