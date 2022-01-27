<?php

use Slim\App;
use Slim\Routing\RouteContext;

return function (App $app) {

  //TODO: Maybe do CORS correctly instead of catch-all?
    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });
    $app->add(function ($request, $handler) {
        $methods = RouteContext::fromRequest($request)->getRoutingResults()->getAllowedMethods();

        $response = $handler->handle($request);
        return $response
              ->withHeader('Access-Control-Allow-Origin', '*')//Detect?
              ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
              ->withHeader('Access-Control-Allow-Methods', implode(",", $methods));
        ;
    });
};
