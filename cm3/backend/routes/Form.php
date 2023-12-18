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
    //TODO: These permissions aren't right...
    $accessPerm = $container->get(PermCheckEventPerm::class)->withAllowedPerm(PermEvent::Attendee_Manage());
    $app->group(
        '/Form/Question/{context_code}',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\Form\Question\Search::class);
            $app->post('', \CM3_Lib\Action\Form\Question\Create::class)
            ->add($accessPerm);
            $app->get('/{id}', \CM3_Lib\Action\Form\Question\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\Form\Question\Update::class)
            ->add($accessPerm);
            $app->post('/{id}/Move', \CM3_Lib\Action\Form\Question\Move::class)
            ->add($accessPerm);
            $app->delete('/{id}', \CM3_Lib\Action\Form\Question\Delete::class)
            ->add($accessPerm);
        }
    );
    $app->group(
        '/Form/Question/{context_code}/{badge_type_id}/Map',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\Form\Question\Map\Search::class);
            $app->post('/{question_id}', \CM3_Lib\Action\Form\Question\Map\Create::class)
            ->add($accessPerm);
            $app->delete('/{question_id}', \CM3_Lib\Action\Form\Question\Map\Delete::class)
            ->add($accessPerm);
        }
    );

    $app->group(
        '/Form/Response/{context_code}/{context_id}',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\Form\Response\Search::class);
            $app->get('/{id}', \CM3_Lib\Action\Form\Response\Read::class);
            $app->post('/{id}', \CM3_Lib\Action\Form\Response\Create::class)
            ->add($accessPerm);
            $app->delete('/{id}', \CM3_Lib\Action\Form\Response\Delete::class)
            ->add($accessPerm);
        }
    );
};
