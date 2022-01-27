<?php

// Define app routes

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return function (App $app) {
    $app->group(
        '/EventInfo',
        function (RouteCollectorProxy $app) {
            $app->get('', \CM3_Lib\Action\EventInfo\Search::class);
            $app->post('', \CM3_Lib\Action\EventInfo\Create::class);
            $app->get('/{id}', \CM3_Lib\Action\EventInfo\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\EventInfo\Update::class);
            $app->delete('/{id}', \CM3_Lib\Action\EventInfo\Delete::class);
        }
    );
};
