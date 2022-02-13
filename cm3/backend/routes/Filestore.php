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
    $accessPerm = $container->get(PermCheckEventPerm::class)->withAllowedPerm(PermEvent::Filestore_Manage());
    $app->group(
        '/Filestore',
        function (RouteCollectorProxy $app) use ($accessPerm) {
            $app->get('', \CM3_Lib\Action\Filestore\Search::class);
            $app->post('', \CM3_Lib\Action\Filestore\Create::class)
            ->add($accessPerm);
            $app->get('/{id}', \CM3_Lib\Action\Filestore\Read::class);
            $app->get('/{id}/{extra:.+}', \CM3_Lib\Action\Filestore\FetchBlob::class);
            $app->post('/{id}', \CM3_Lib\Action\Filestore\Update::class)
            ->add($accessPerm);
            $app->delete('/{id}', \CM3_Lib\Action\Filestore\Delete::class)
            ->add($accessPerm); //Only global admins can delete
        }
    );
};
