<?php

// Define app routes

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

return function (App $app) {
    $app->group(
        '/public',
        function (RouteCollectorProxy $app) {
            //Default root, lists available events
            $app->get('', \CM3_Lib\Action\Public\ListEventInfo::class);

            $app->get('/{event_id}/badges', \CM3_Lib\Action\Public\ListBadgeContexts::class);
            //What badges are available for a given context
            $app->get('/{event_id}/badges/A', \CM3_Lib\Action\Public\ListAttendeeBadges::class);
            $app->get('/{event_id}/badges/A/{badge_id}/addons', \CM3_Lib\Action\Public\ListAttendeeAddons::class);
            $app->get('/{event_id}/badges/S', \CM3_Lib\Action\Public\ListStaffBadges::class);
            $app->get('/{event_id}/badges/{context}', \CM3_Lib\Action\Public\ListApplicationBadges::class);

            //Questions, retrieves the associated form questions for a badge
            $app->get('/{event_id}/questions/{context}/{context_id}', \CM3_Lib\Action\Public\ListQuestions::class);

            //Log in. Also allows selecting a different event id.
            $app->post('/login', \CM3_Lib\Action\Public\Login::class);

            //Register contact
            $app->post('/createaccount', \CM3_Lib\Action\Public\CreateAccount::class);

            //Request magic link
            $app->post('/requestmagic', \CM3_Lib\Action\Public\SendMagicLink::class);
        }
    );
};
