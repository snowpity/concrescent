<?php

use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use CM3_Lib\Middleware\GZCompress;

use CM3_Lib\util\EventPermissions;
use CM3_Lib\util\CurrentUserInfo;

use MessagePack\BufferUnpacker;

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
            //Load up the unpacker
            $unpacker = (new BufferUnpacker())
                ->extendWith(new EventPermissions());
            $unpacker->reset($arguments["decoded"]);

            //Get the Contact ID first
            $contact_id = $unpacker->unpack();
            //And their selected event ID
            $event_id = $unpacker->unpack();

            $perms = new EventPermissions();
            //Does this token have permissions?
            if ($unpacker->hasRemaining()) {
                //Ooh, has admin permissions! Decode that...
                $perms = $unpacker->unpack();
            }
            //Tell the CurrentUserInfo who it is
            $CurrentUserInfo = $app->getContainer()->get(CM3_Lib\util\CurrentUserInfo::class);
            $CurrentUserInfo->SetEventId($event_id);
            $CurrentUserInfo->SetContactId($contact_id);

            //Throw the result in as attributes
            return $request
              ->withAttribute("contact_id", $contact_id)
              ->withAttribute("event_id", $event_id)
              ->withAttribute("perms", $perms);
        }
    ]));

    $app->add(ErrorMiddleware::class);
};
