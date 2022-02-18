<?php

namespace CM3_Lib\Action\Attendee\Badge;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\attendee\badge;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class Search
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
        private badgetype $badgetype
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
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        //Grab the list of valid badge_type_ids
        $badge_type_ids = array_column($this->badgetype->Search(
            array('id'),
            array(new SearchTerm('event_id', $request->getAttribute('event_id')))
        ), 'id');

        $whereParts = array(
          new SearchTerm('badge_type_id', $badge_type_ids, 'IN')
        );

        $order = array('id' => false);

        $page      = ($request->getQueryParams()['page']?? 0 > 0) ? $request->getQueryParams()['page'] : 1;
        $limit     = $request->getQueryParams()['itemsPerPage']?? -1; // Number of posts on one page
        $offset      = ($page - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->badge->Search(array(), $whereParts, $order, $limit, $offset);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
