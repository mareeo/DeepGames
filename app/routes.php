<?php
declare(strict_types=1);

use DeepGamers\Application\Actions\HomeAction;
use DeepGamers\Application\Actions\RootApiAction;
use Slim\App;

return function (App $app) {

    $app->get('/', HomeAction::class);


    $app->group('/api', function (\Slim\Routing\RouteCollectorProxy $group) {

        $group->get('/', RootApiAction::class);

        $group->get('/channels', \DeepGamers\Application\Actions\ApiChannelsAction::class);
    });

    $app->group('/imgdump', function(\Slim\Routing\RouteCollectorProxy $group) {
        $group->get('/', \DeepGamers\Application\Actions\ImgDumpPageAction::class);
        $group->post('/submit.php', \DeepGamers\Application\Actions\ImgDumpSubmitAction::class);
        $group->get('/remove.php', \DeepGamers\Application\Actions\ImgDumpRemoveAction::class);
    });























};
