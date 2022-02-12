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
    $attendeePerm = $container->get(PermCheckEventPerm::class);
    $app->group(
        '/Attendee',
        function (RouteCollectorProxy $app) use ($attendeePerm) {
            $app->get('', \CM3_Lib\Action\Attendee\Search::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_View()
            )));
            $app->post('/export', \CM3_Lib\Action\Attendee\Export::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_Export()
            )));
            $app->post('', \CM3_Lib\Action\Attendee\Create::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_Edit()
            )));
            $app->get('/{id}', \CM3_Lib\Action\Attendee\Read::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_View(),
                PermEvent::Attendee_Edit()
            )));
            $app->post('/{id}', \CM3_Lib\Action\Attendee\Update::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_View(),
                PermEvent::Attendee_Edit()
            )));
            $app->delete('/{id}', \CM3_Lib\Action\Attendee\Delete::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_Refund()
            )));
        }
    );
};
