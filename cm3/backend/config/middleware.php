<?php

use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use CM3_Lib\Middleware\GZCompress;

use MessagePack\BufferUnpacker;

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

    //Branca token authenticator
    $app->add(new Tuupola\Middleware\BrancaAuthentication([
        "ttl" => $s_config['environment']['token_life'],
        "secret" => $s_config['environment']['token_secret'],
        "ignore" =>  $s_config['environment']['base_path'] .'/public',
        "before" => function ($request, $arguments) {
            //Load up the unpacker
            $unpacker = new BufferUnpacker();
            $unpacker->reset($arguments["decoded"]);

            //Get the Contact ID first
            $contact_id = $unpacker->unpack();
            //And their selected event ID
            $event_id = $unpacker->unpack();
            //Todo: This should be an object
            $perms = array(
              'has_perms'=>false,
              'account_level'=>array(),
              'app_groups' => array()
          );

            if ($unpacker->hasRemaining()) {
                //Ooh, has admin permissions! Decode that...
                $perms['has_perms'] = true;
            }

            //Throw the result in as attributes
            return $request
              ->withAttribute('rawtoken', $arguments['decoded'])
              ->withAttribute("contact_id", $contact_id)
              ->withAttribute("event_id", $event_id)
              ->withAttribute("perms", $perms);
        }
    ]));

    $app->add(ErrorMiddleware::class);
};
