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
            $app->get('/{event_id}/badges/A/addons', \CM3_Lib\Action\Public\ListAllAttendeeAddons::class);
            $app->get('/{event_id}/badges/A/{badge_id}/addons', \CM3_Lib\Action\Public\ListAttendeeAddons::class);
            $app->get('/{event_id}/badges/S', \CM3_Lib\Action\Public\ListStaffBadges::class);
            //Dummy until staff badge types can get addons...
            $app->get('/{event_id}/badges/S/addons', function ($request, $response, $params) {
                $response->getBody()->write("[]");
                return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            });
            $app->get('/{event_id}/badges/{context_code}', \CM3_Lib\Action\Public\ListApplicationBadges::class);
            $app->get('/{event_id}/badges/{context_code}/{badge_id}/addons', \CM3_Lib\Action\Public\ListApplicationAddons::class);
            $app->get('/{event_id}/badges/{context_code}/addons', \CM3_Lib\Action\Public\ListAllApplicationAddons::class);

            //Questions, retrieves the associated form questions for a badge
            $app->get('/{event_id}/questions/{context_code}', \CM3_Lib\Action\Public\ListAllQuestions::class);
            $app->get('/{event_id}/questions/{context_code}/{context_id}', \CM3_Lib\Action\Public\ListQuestions::class);

            //Fetch a blob file
            $app->get('/{event_id}/file/{id}/{extra:.+}', \CM3_Lib\Action\Public\GetFile::class);

            //Fetch available payment methods
            $app->get('/paymethods', \CM3_Lib\Action\Public\ListPayMethods::class);

            //An anonymous user has a badge link
            $app->get('/getspecificbadge', \CM3_Lib\Action\Public\GetSpecificBadge::class);

            //Log in. Also allows selecting a different event id.
            $app->post('/login', \CM3_Lib\Action\Public\Login::class);

            //Register contact
            $app->post('/createaccount', \CM3_Lib\Action\Public\CreateAccount::class);

            //Request magic link
            $app->post('/requestmagic', \CM3_Lib\Action\Public\SendMagicLink::class);
        }
    );
};
