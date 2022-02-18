<?php

namespace CM3_Lib\Action\Attendee\AddonMap;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\attendee\addon;
use CM3_Lib\models\attendee\addonmap;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;

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
    public function __construct(private Responder $responder, private addon $addon, private addonmap $addonmap)
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
        $event_id = $request->getAttribute('event_id');

        //Confirm the given addon_id belongs to the given event_id
        if (!$this->addon->verifyAddonBelongsToEvent($params['addon_id'], $event_id)) {
            throw new HttpBadRequestException($request, 'Invalid addon_id specified');
        }

        $whereParts = array(
            new SearchTerm('addon_id', $params['addon_id'])
          //new SearchTerm('active', 1)
        );

        $order = array('badge_type_id'=>false);

        $page      = ($request->getQueryParams()['page']?? 0 > 0) ? $request->getQueryParams()['page'] : 1;
        $limit     = $request->getQueryParams()['itemsPerPage']?? -1; // Number of posts on one page
        $offset      = ($page - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->addonmap->Search(array('badge_type_id'), $whereParts, $order, $limit, $offset);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
