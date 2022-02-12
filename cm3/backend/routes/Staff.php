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
    $staffPerm = $container->get(PermCheckEventPerm::class);
    $app->group(
        '/Staff',
        function (RouteCollectorProxy $app) use ($staffPerm) {
            $app->get('', \CM3_Lib\Action\Staff\Search::class)
            ->add($staffPerm->withAllowedPerms(array(
                PermEvent::Staff_View()
            )));
            $app->post('/export', \CM3_Lib\Action\Staff\Export::class)
            ->add($staffPerm->withAllowedPerms(array(
                PermEvent::Staff_Export()
            )));
            $app->post('', \CM3_Lib\Action\Staff\Create::class)
            ->add($staffPerm->withAllowedPerms(array(
                PermEvent::Staff_Edit()
            )));
            $app->get('/{id}', \CM3_Lib\Action\Staff\Read::class)
            ->add($staffPerm->withAllowedPerms(array(
                PermEvent::Staff_View(),
                PermEvent::Staff_Edit()
            )));
            $app->post('/{id}', \CM3_Lib\Action\Staff\Update::class)
            ->add($staffPerm->withAllowedPerms(array(
                PermEvent::Staff_Review(),
                PermEvent::Staff_Edit()
            )));
            $app->delete('/{id}', \CM3_Lib\Action\Staff\Delete::class)
            ->add($staffPerm->withAllowedPerms(array(
                PermEvent::Staff_Edit()
            )));
        }
    );
};
