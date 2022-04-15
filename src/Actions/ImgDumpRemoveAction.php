<?php


namespace App\Actions;

use App\Services\ImgDumpService;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

class ImgDumpRemoveAction
{
    /** @var ImgDumpService */
    private $imgDump;

    public function __construct(ImgDumpService $imgDump)
    {
        $this->imgDump = $imgDump;
    }

    public function __invoke(ServerRequest $request, Response $response, $args): Response
    {
        return $this->imgDump->removeImage($request, $response);
    }
}