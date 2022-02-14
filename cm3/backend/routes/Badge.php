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
    $accessPerm = $container->get(PermCheckEventPerm::class)->withAllowedPerm(PermEvent::Badge_ManageFormat());
    $app->group(
        '/Badge/Format',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\Badge\Format\Search::class);
            $app->post('', \CM3_Lib\Action\Badge\Format\Create::class)
            ->add($accessPerm);
            $app->get('/{id}', \CM3_Lib\Action\Badge\Format\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\Badge\Format\Update::class)
            ->add($accessPerm);
            $app->delete('/{id}', \CM3_Lib\Action\Badge\Format\Delete::class)
            ->add($accessPerm);
        }
    );
    $app->group(
        '/Badge/Format/{format_id}/Map',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\Badge\Format\Map\Search::class);
            $app->post('/{category}/{badge_type_id}', \CM3_Lib\Action\Badge\Format\Map\Create::class)
            ->add($accessPerm);
            $app->delete('/{category}/{badge_type_id}', \CM3_Lib\Action\Badge\Format\Map\Delete::class)
            ->add($accessPerm);
        }
    );
};
