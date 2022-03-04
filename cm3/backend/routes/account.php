<?php

// Define app routes

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return function (App $app) {
    $app->group(
        '/account',
        function (RouteCollectorProxy $app) {
            //Default root, Gets currently logged-in usere info, including admin permissions, if applicable
            $app->get('', \CM3_Lib\Action\Account\GetAccount::class);
            //Save account details
            $app->post('', \CM3_Lib\Action\Account\SetAccount::class);
            //Refresh the token
            $app->get('/refreshtoken', \CM3_Lib\Action\Account\RefreshToken::class);
            //Switch which event we're talking about
            $app->post('/switchevent', \CM3_Lib\Action\Account\SwitchEvent::class);

            //What badges have been saved
            $app->get('/badges', \CM3_Lib\Action\Account\GetMyBadges::class);
            //What applications have been saved
            $app->get('/applications', \CM3_Lib\Action\Account\GetMyApplications::class);

            //Retrieve responses to forms
            $app->get('/formresponses', \CM3_Lib\Action\Account\GetMyResponses::class);

            //Retrieve cart(s)
            $app->get('/GetCart', \CM3_Lib\Action\Account\GetCart::class);
            //Save cart
            $app->post('/SaveCart', \CM3_Lib\Action\Account\SaveCart::class);
            //Checkout cart
            $app->post('/CheckoutCart', \CM3_Lib\Action\Account\CheckoutCart::class);
        }
    );
};
