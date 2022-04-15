<?php


namespace App\Actions;

use App\ImgDump;
use App\Services\ImgDumpService;
use League\Plates\Engine;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

class ImgDumpPageAction
{
    /** @var Engine */
    private $plates;

    /** @var ImgDumpService */
    private $imgDump;

    public function __construct(Engine $engine, ImgDumpService $imgDump)
    {
        $this->plates = $engine;
        $this->imgDump = $imgDump;
    }

    public function __invoke(ServerRequest $request, Response $response, $args): Response
    {
        $images = $this->imgDump->getImages();
        $pageSelectHtml = $this->imgDump->displayPageSelect($images);

        $cookies = $request->getCookieParams();

        $uploaderUuid = $cookies['uuid'] ?? null;

        $body = $this->plates->render('imgdump', [
            'images' => $images,
            'uploaderUuid' => $uploaderUuid,
            'pageSelectHtml' => $pageSelectHtml
        ]);
        $response->getBody()->write($body);
        return $response;
    }
}