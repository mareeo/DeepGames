<?php
declare(strict_types=1);

use App\Actions\HomeAction;
use App\Actions\RootApiAction;
use Slim\App;

return function (App $app) {
    $app->get('/', HomeAction::class);

    $app->group('/api', function (\Slim\Routing\RouteCollectorProxy $group) {
        $group->get('/', RootApiAction::class);
        $group->get('/channels', \App\Actions\ApiChannelsAction::class);
    });
};
