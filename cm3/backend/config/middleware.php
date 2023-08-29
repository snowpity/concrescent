<?php

use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use CM3_Lib\Middleware\GZCompress;

return function (App $app, $s_config) {
    $app->setBasePath($s_config['environment']['base_path']);

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

    //Branca token authenticator
    $app->add(new Tuupola\Middleware\BrancaAuthentication([
        "ttl" => $s_config['environment']['token_life'],
        "secret" => $s_config['environment']['token_secret'],
        "ignore" =>  $s_config['environment']['base_path'] .'/public',
        "before" => function ($request, $arguments) use ($app) {
            //Load the CurrentUserInfo with the token data
            $CurrentUserInfo = $app->getContainer()->get(CM3_Lib\util\CurrentUserInfo::class);
            $CurrentUserInfo->fromToken($arguments['decoded']);

            //Throw the result in as attributes
            return $request
              ->withAttribute("contact_id", $CurrentUserInfo->GetContactId())
              ->withAttribute("event_id", $CurrentUserInfo->GetEventId())
              ->withAttribute("perms", $CurrentUserInfo->GetPerms());
        }
    ]));

    $app->add(ErrorMiddleware::class);
};
