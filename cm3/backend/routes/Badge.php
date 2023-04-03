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
    $app->group(
        '/Badge/Format/{format_id}/Badges',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $printPerm = $accessPerm->withAllowedPerm(PermEvent::Badge_Print());
            $app->get('', \CM3_Lib\Action\Badge\Format\Badges\Search::class)
            ->add($printPerm);
            $app->post('/{context_code}/{badge_id}', \CM3_Lib\Action\Badge\Format\Badges\Create::class)
            ->add($printPerm);
        }
    );
    $app->group(
        '/Badge/FormatMap/{context_code}',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\Badge\Format\Map\SearchAll::class);
            $app->get('/{badge_type_id}', \CM3_Lib\Action\Badge\Format\Map\Search::class);
            $app->post('/badge_type_id/{format_id}', \CM3_Lib\Action\Badge\Format\Map\Create::class)
            ->add($accessPerm);
            $app->delete('/badge_type_id/{format_id}', \CM3_Lib\Action\Badge\Format\Map\Delete::class)
            ->add($accessPerm);
        }
    );

    $app->group(
        '/Badge/Format/{format_id}/PrintJob',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $printPerm = $accessPerm->withAllowedPerm(PermEvent::Badge_Print());
            $app->get('', \CM3_Lib\Action\Badge\Format\PrintJob\Search::class);
            $app->post('', \CM3_Lib\Action\Badge\Format\PrintJob\Create::class);
            $app->get('/{id}', \CM3_Lib\Action\Badge\Format\PrintJob\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\Badge\Format\PrintJob\Update::class)
            ->add($printPerm);
            $app->delete('/{id}', \CM3_Lib\Action\Badge\Format\PrintJob\Delete::class)
            ->add($printPerm);
        }
    );

    $app->group(
        '/Badge/PrintJob',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $printPerm = $accessPerm->withAllowedPerm(PermEvent::Badge_Print());
            $app->get('', \CM3_Lib\Action\Badge\PrintJob\Search::class)
            ->add($printPerm);
            $app->get('/{id}', \CM3_Lib\Action\Badge\PrintJob\Read::class)
            ->add($printPerm);
            $app->post('/{id}', \CM3_Lib\Action\Badge\PrintJob\Update::class)
            ->add($printPerm);
        }
    );

    $app->group(
        '/Badge/CheckIn',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $checkinPerm = $accessPerm->withAllowedPerm(PermEvent::Badge_Checkin());
            $app->get('', \CM3_Lib\Action\Badge\CheckIn\Search::class)
            ->add($checkinPerm);
            $app->get('/{context_code}/{badge_id}', \CM3_Lib\Action\Badge\CheckIn\Read::class)
            ->add($checkinPerm);
            $app->post('/{context_code}/{badge_id}/Update', \CM3_Lib\Action\Badge\CheckIn\Update::class)
            ->add($checkinPerm);
            $app->get('/{context_code}/{badge_id}/GetPayment', \CM3_Lib\Action\Badge\CheckIn\GetPayment::class)
            ->add($checkinPerm);
            $app->post('/{context_code}/{badge_id}/PostPayment', \CM3_Lib\Action\Badge\CheckIn\PostPayment::class)
            ->add($checkinPerm);
            $app->post('/{context_code}/{badge_id}/Print', \CM3_Lib\Action\Badge\CheckIn\PostPrint::class)
            ->add($checkinPerm);
            $app->get('/{context_code}/{badge_id}/Print/{job_id}', \CM3_Lib\Action\Badge\CheckIn\GetPrint::class)
            ->add($checkinPerm);
            $app->post('/{context_code}/{badge_id}/Finish', \CM3_Lib\Action\Badge\CheckIn\FinishCheckIn::class)
            ->add($checkinPerm);
        }
    );
};
