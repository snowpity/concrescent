<?php

namespace CM3_Lib\Action\Staff\Badge;

use CM3_Lib\models\staff\badge;
use CM3_Lib\models\staff\badgetype;
use CM3_Lib\models\contact;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class Create
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private badge $badge,
        private badgetype $badgetype,
        private contact $contact
    ) {
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();

        //Confirm the given badge_type_id belongs to the given group_id
        if (!$this->badgetype->verifyBadgeTypeBelongsToEvent($data['badge_type_id'], $request->getAttribute('event_id'))) {
            throw new HttpBadRequestException($request, 'Invalid badge_type_id specified');
        }

        //Confirm the selected contact exists
        if (!$this->contact->Exists($data['contact_id'])) {
            throw new HttpBadRequestException($request, 'Invalid contact_id specified');
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->staff->Create($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
