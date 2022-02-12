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
        '/Contact',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\Contact\Search::class);
            $app->post('', \CM3_Lib\Action\Contact\Create::class);
            $app->get('/{id}', \CM3_Lib\Action\Contact\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\Contact\Update::class);
            $app->delete('/{id}', \CM3_Lib\Action\Contact\Delete::class)
            ->add($accessPerm); //Only global admins can delete
        }
    );
};
