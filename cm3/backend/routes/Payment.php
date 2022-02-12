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
    $payPerm = $container->get(PermCheckEventPerm::class);
    $app->group(
        '/Payment',
        function (RouteCollectorProxy $app) use ($payPerm) {
            $app->get('', \CM3_Lib\Action\Payment\Search::class)
            ->add($payPerm->withAllowedPerms(array(PermEvent::Payment_View())));
            $app->post('', \CM3_Lib\Action\Payment\Create::class)
            ->add($payPerm->withAllowedPerms(array(PermEvent::Payment_CreateCancel())));
            $app->get('/{id}', \CM3_Lib\Action\Payment\Read::class)
            ->add($payPerm->withAllowedPerms(array(PermEvent::Payment_View())));
            $app->post('/{id}', \CM3_Lib\Action\Payment\Update::class)
            ->add($payPerm->withAllowedPerms(array(PermEvent::Payment_Edit())));
            $app->delete('/{id}', \CM3_Lib\Action\Payment\Delete::class)
            ->add($payPerm->withAllowedPerms(array(PermEvent::Payment_CreateCancel())));
        }
    );
};
