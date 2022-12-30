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
    $globalPerm = $container->get(PermCheckEventPerm::class)
    ->withAllowedPerm(PermEvent::GlobalAdmin());
    $staffView = $globalPerm->withAllowedPerm(PermEvent::Staff_View());

    $r = array(
        '/ErrorLog' =>
        function (RouteCollectorProxy $app) use ($globalPerm) {
            $app->get('', \CM3_Lib\Action\System\ErrorLog\Search::class)
            ->add($globalPerm);
            $app->post('/export', \CM3_Lib\Action\System\ErrorLog\Export::class)
            ->add($globalPerm);
            $app->post('', \CM3_Lib\Action\System\ErrorLog\Create::class)
            ->add($globalPerm);
            $app->get('/{id}', \CM3_Lib\Action\System\ErrorLog\Read::class)
            ->add($globalPerm);
            $app->post('/{id}', \CM3_Lib\Action\System\ErrorLog\Update::class)
            ->add($globalPerm);
        },
        // '/BadgeType' => function (RouteCollectorProxy $app) use ($globalPerm, $staffView) {
        //     $app->get('', \CM3_Lib\Action\System\ErrorLogType\Search::class)
        //     ->add($staffView);
        //     $app->post('', \CM3_Lib\Action\System\ErrorLogType\Create::class)
        //     ->add($globalPerm);
        //     $app->get('/{id}', \CM3_Lib\Action\System\ErrorLogType\Read::class)
        //     ->add($staffView);
        //     $app->post('/{id}', \CM3_Lib\Action\System\ErrorLogType\Update::class)
        //     ->add($globalPerm);
        //     $app->delete('/{id}', \CM3_Lib\Action\System\ErrorLogType\Delete::class)
        //     ->add($globalPerm);
        // },
        // '/Department' => function (RouteCollectorProxy $app) use ($globalPerm, $staffView) {
        //     $app->get('', \CM3_Lib\Action\Staff\Department\Search::class)
        //     ->add($staffView);
        //     $app->post('', \CM3_Lib\Action\Staff\Department\Create::class)
        //     ->add($globalPerm);
        //     $app->get('/{id}', \CM3_Lib\Action\Staff\Department\Read::class)
        //     ->add($staffView);
        //     $app->post('/{id}', \CM3_Lib\Action\Staff\Department\Update::class)
        //     ->add($globalPerm);
        //     $app->delete('/{id}', \CM3_Lib\Action\Staff\Department\Delete::class)
        //     ->add($globalPerm);
        //     $app->group('/{department_id}/Position', function (RouteCollectorProxy $app) use ($globalPerm, $staffView) {
        //         $app->get('', \CM3_Lib\Action\Staff\Position\Search::class)
        //         ->add($staffView);
        //         $app->post('', \CM3_Lib\Action\Staff\Position\Create::class)
        //         ->add($globalPerm);
        //         $app->get('/{id}', \CM3_Lib\Action\Staff\Position\Read::class)
        //         ->add($staffView);
        //         $app->post('/{id}', \CM3_Lib\Action\Staff\Position\Update::class)
        //         ->add($globalPerm);
        //         $app->delete('/{id}', \CM3_Lib\Action\Staff\Position\Delete::class)
        //         ->add($globalPerm);
        //     });
        // },
    );

    $app->group(
        '/System',
        function (RouteCollectorProxy $app) use ($r, $container) {
            //Add all the sub-routes
            foreach ($r as $route => $definition) {
                $app->group($route, $definition);
            }
        }
    );
};
