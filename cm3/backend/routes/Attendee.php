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

    $r = array(
        '/Badge' =>
        function (RouteCollectorProxy $app) use ($attendeePerm) {
            $app->get('', \CM3_Lib\Action\Attendee\Badge\Search::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_View()
            )));
            $app->post('/export', \CM3_Lib\Action\Attendee\Badge\Export::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_Export()
            )));
            $app->post('', \CM3_Lib\Action\Attendee\Badge\Create::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_Edit()
            )));
            $app->get('/{id}', \CM3_Lib\Action\Attendee\Badge\Read::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_View(),
                PermEvent::Attendee_Edit()
            )));
            $app->post('/{id}', \CM3_Lib\Action\Attendee\Badge\Update::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_View(),
                PermEvent::Attendee_Edit()
            )));
            $app->delete('/{id}', \CM3_Lib\Action\Attendee\Badge\Delete::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_Refund()
            )));
        },
        '/BadgeType' => function (RouteCollectorProxy $app) use ($attendeePerm) {
            $atManage = $attendeePerm->withAllowedPerm(PermEvent::Attendee_Manage());
            $app->get('', \CM3_Lib\Action\Attendee\BadgeType\Search::class)
            ->add($attendeePerm);
            $app->post('', \CM3_Lib\Action\Attendee\BadgeType\Create::class)
            ->add($atManage);
            $app->get('/{id}', \CM3_Lib\Action\Attendee\BadgeType\Read::class)
            ->add($attendeePerm);
            $app->post('/{id}', \CM3_Lib\Action\Attendee\BadgeType\Update::class)
            ->add($atManage);
            $app->delete('/{id}', \CM3_Lib\Action\Attendee\BadgeType\Delete::class)
            ->add($atManage);
        },
        '/Addon' => function (RouteCollectorProxy $app) use ($attendeePerm) {
            $atManage = $attendeePerm->withAllowedPerm(PermEvent::Attendee_Manage());
            $app->get('', \CM3_Lib\Action\Attendee\Addon\Search::class)
            ->add($attendeePerm);
            $app->post('', \CM3_Lib\Action\Attendee\Addon\Create::class)
            ->add($atManage);
            $app->get('/{id}', \CM3_Lib\Action\Attendee\Addon\Read::class)
            ->add($attendeePerm);
            $app->post('/{id}', \CM3_Lib\Action\Attendee\Addon\Update::class)
            ->add($atManage);
            $app->delete('/{id}', \CM3_Lib\Action\Attendee\Addon\Delete::class)
            ->add($atManage);
            $app->get('/{addon_id}/Badge', \CM3_Lib\Action\Attendee\AddonMap\Search::class)
            ->add($attendeePerm);
            $app->post('/{addon_id}/Badge/{badge_type_id}', \CM3_Lib\Action\Attendee\AddonMap\Create::class)
            ->add($atManage);
            $app->delete('/{addon_id}/Badge/{badge_type_id}', \CM3_Lib\Action\Attendee\AddonMap\Delete::class)
            ->add($atManage);
            $app->get('/{addon_id}/Purchase', \CM3_Lib\Action\Attendee\AddonPurchase\Search::class)
            ->add($attendeePerm);
        },
        '/Badge/{attendee_id}/AddonPurchase' =>
        function (RouteCollectorProxy $app) use ($attendeePerm) {
            $app->get('', \CM3_Lib\Action\Attendee\AddonPurchase\Search::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_View()
            )));
            $app->post('/export', \CM3_Lib\Action\Attendee\AddonPurchase\Export::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_Export()
            )));
            $app->post('', \CM3_Lib\Action\Attendee\AddonPurchase\Create::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_Edit()
            )));
            $app->get('/{id}', \CM3_Lib\Action\Attendee\AddonPurchase\Read::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_View(),
                PermEvent::Attendee_Edit()
            )));
            $app->post('/{id}', \CM3_Lib\Action\Attendee\AddonPurchase\Update::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_View(),
                PermEvent::Attendee_Edit()
            )));
            $app->delete('/{id}', \CM3_Lib\Action\Attendee\AddonPurchase\Delete::class)
            ->add($attendeePerm->withAllowedPerms(array(
                PermEvent::Attendee_Refund()
            )));
        },
        '/PromoCode' => function (RouteCollectorProxy $app) use ($attendeePerm) {
            $atManage = $attendeePerm->withAllowedPerm(PermEvent::Attendee_Manage());
            $app->get('', \CM3_Lib\Action\Attendee\PromoCode\Search::class)
            ->add($attendeePerm);
            $app->post('', \CM3_Lib\Action\Attendee\PromoCode\Create::class)
            ->add($atManage);
            $app->get('/{id}', \CM3_Lib\Action\Attendee\PromoCode\Read::class)
            ->add($attendeePerm);
            $app->post('/{id}', \CM3_Lib\Action\Attendee\PromoCode\Update::class)
            ->add($atManage);
            $app->delete('/{id}', \CM3_Lib\Action\Attendee\PromoCode\Delete::class)
            ->add($atManage);
        },
    );

    $app->group(
        '/Attendee',
        function (RouteCollectorProxy $app) use ($r) {
            //Add all the sub-routes
            foreach ($r as $route => $definition) {
                $app->group($route, $definition);
            }
        }
    );
};
