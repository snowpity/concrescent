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
    $accessPerm = $container->get(PermCheckEventPerm::class)->withAllowedPerm(PermEvent::Location_Manage());
    $app->group(
        '/Location',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\Location\Search::class);
            $app->post('', \CM3_Lib\Action\Location\Create::class)
            ->add($accessPerm);
            $app->get('/{id}', \CM3_Lib\Action\Location\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\Location\Update::class)
            ->add($accessPerm);
            $app->delete('/{id}', \CM3_Lib\Action\Location\Delete::class)
            ->add($accessPerm);
        }
    );
    $app->group(
        '/LocationMap',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\LocationMap\Search::class);
            $app->post('', \CM3_Lib\Action\LocationMap\Create::class)
            ->add($accessPerm);
            $app->get('/{id}', \CM3_Lib\Action\LocationMap\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\LocationMap\Update::class)
            ->add($accessPerm);
            $app->delete('/{id}', \CM3_Lib\Action\LocationMap\Delete::class)
            ->add($accessPerm);
        }
    );

    $app->group(
        '/LocationMap/{map_id}/Coord',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\LocationMap\Coord\Search::class);
            $app->post('', \CM3_Lib\Action\LocationMap\Coord\Create::class)
            ->add($accessPerm);
            $app->get('/{id}', \CM3_Lib\Action\LocationMap\Coord\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\LocationMap\Coord\Update::class)
            ->add($accessPerm);
            $app->delete('/{id}', \CM3_Lib\Action\LocationMap\Coord\Delete::class)
            ->add($accessPerm);
        }
    );
};
