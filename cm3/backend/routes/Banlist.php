<?php

// Define app routes
use CM3_Lib\util\PermEvent;
use CM3_Lib\Middleware\PermCheckEventPerm;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return function (App $app, $container) {
    $accessPerm = $container->get(PermCheckEventPerm::class);
    $app->group(
        '/Banlist',
        function (RouteCollectorProxy $app) {
            $app->get('', \CM3_Lib\Action\Banlist\Search::class);
            $app->post('', \CM3_Lib\Action\Banlist\Create::class);
            $app->get('/{id}', \CM3_Lib\Action\Banlist\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\Banlist\Update::class);
            $app->delete('/{id}', \CM3_Lib\Action\Banlist\Delete::class);
        }
    )->add($accessPerm->withAllowedPerms(array(PermEvent::Manage_Banlist())));
};
