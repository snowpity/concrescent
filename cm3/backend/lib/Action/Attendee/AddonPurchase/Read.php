<?php

namespace CM3_Lib\Action\Attendee\AddonPurchase;

use CM3_Lib\models\attendee\addonpurchase;
use CM3_Lib\models\attendee\badge;
use CM3_Lib\models\attendee\badgetype;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class Read
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private addonpurchase $addonpurchase, private badge $badge, private badgetype $badgetype)
    {
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $params): ResponseInterface
    {
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        //Confirm permission to read this badge applicant
        $badgeinfo = $this->badge->GetByID($params['attendee_id'], new View(
            array('id'),
            array(
                new Join($this->badgetype, array('id'=>'badge_type_id', new SearchTerm('event_id', $params['event_id'])))
            )
        ));

        if ($badgeinfo === false) {
            throw new HttpBadRequestException($request, 'Invalid badge specified');
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->addonpurchase->GetByID($params['id'], new View(
            array(),
            array(new Join($this->badge, array(
                'id'=>'attendee_id',
                 new SearchTerm('attendee_id', $params['attendee_id'])
             )))
        ));

        if ($data === false) {
            throw new HttpNotFoundException($request);
        }


        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
