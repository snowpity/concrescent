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
    $accessPerm = $container->get(PermCheckEventPerm::class)->withAllowedPerm(PermEvent::EventAdmin());
    $app->group(
        '/Group',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\Group\Search::class);
            $app->post('', \CM3_Lib\Action\Group\Create::class);
            $app->get('/{id}', \CM3_Lib\Action\Group\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\Group\Update::class);
            $app->delete('/{id}', \CM3_Lib\Action\Group\Delete::class)
            ->add($accessPerm); //Only global admins can delete
        }
    );
};
