<?php

use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use CM3_Lib\Middleware\GZCompress;

return function (App $app, $s_config) {
    $app->setBasePath($s_config['environment']['base_path']);
    $app->addBodyParsingMiddleware();

    /*
     * The routing middleware should be added earlier than the ErrorMiddleware
     * Otherwise exceptions thrown from it will not be handled by the middleware
     */
    $app->addRoutingMiddleware();

    //post body middleware
    $app->addBodyParsingMiddleware();

    // Gzip compression middleware
    if ($s_config['environment']['use_gzip']) {
        $app->add(GZCompress::class);
    }

    $app->add(ErrorMiddleware::class);
};
