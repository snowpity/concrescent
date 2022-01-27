<?php

// Define app routes

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return function (App $app) {
    $app->group(
        '/Payment',
        function (RouteCollectorProxy $app) {
            $app->get('', \CM3_Lib\Action\Payment\Search::class);
            $app->post('', \CM3_Lib\Action\Payment\Create::class);
            $app->get('/{id}', \CM3_Lib\Action\Payment\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\Payment\Update::class);
            $app->delete('/{id}', \CM3_Lib\Action\Payment\Delete::class);
        }
    );
};
