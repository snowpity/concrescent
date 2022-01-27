<?php

// Define app routes

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return function (App $app) {
    $app->group(
        '/AdminUser',
        function (RouteCollectorProxy $app) {
            $app->get('', \CM3_Lib\Action\AdminUser\Search::class);
            $app->post('', \CM3_Lib\Action\AdminUser\Create::class);
            $app->get('/{id}', \CM3_Lib\Action\AdminUser\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\AdminUser\Update::class);
            $app->delete('/{id}', \CM3_Lib\Action\AdminUser\Delete::class);
        }
    );
};
