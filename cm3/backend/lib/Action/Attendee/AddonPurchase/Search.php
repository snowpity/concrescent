<?php

namespace CM3_Lib\Action\Attendee\AddonPurchase;

use CM3_Lib\models\attendee\addonpurchase;
use CM3_Lib\models\attendee\addon;
use CM3_Lib\models\attendee\badge;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\util\badgeinfo;

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
    public function __construct(
        private Responder $responder,
        private addonpurchase $addonpurchase,
        private addon $addon,
        private badge $badge,
        private badgeinfo $badgeinfo
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $params): ResponseInterface
    {
        //Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        //Confirm permission to access this addon

        $addon = $this->addon->GetByID($params['addon_id'], ['event_id']);

        if ($addon === false) {
            throw new HttpBadRequestException($request, 'Addon not found');
        }
        if ($addon['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Addon not found.');
        }


        $whereParts = array(
          new SearchTerm('addon_id', $params['addon_id'])
        );

        $qp = $request->getQueryParams();
        $find = $qp['find'] ?? '';
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $pg = $this->badgeinfo->parseQueryParamsPagination($qp, 'attendee_id', defaultSortDesc:true);
        $totalRows = 0;


        // Invoke the Domain with inputs and retain the result
        $data = $this->addonpurchase->Search(new View(
            array(
                'attendee_id',
                'payment_status',
                'payment_id',
                new SelectColumn('contact_id', JoinedTableAlias: 'sub'),
                new SelectColumn('display_id', JoinedTableAlias: 'sub'),
                new SelectColumn('real_name', JoinedTableAlias: 'sub'),
                new SelectColumn('fandom_name', JoinedTableAlias: 'sub'),
                new SelectColumn('name_on_badge', JoinedTableAlias: 'sub'),
                new SelectColumn('application_status', EncapsulationFunction: "''", Alias:'application_status'),
            ),
            array(
                new Join(
                    $this->badge,
                    array(
                'id' => 'attendee_id'
            ),
                    'left',
                    alias:'sub'
                )
            )
        ), $whereParts, $pg['order'], $pg['limit'], $pg['offset'], $totalRows);

        $response = $response->withHeader('X-Total-Rows', (string)$totalRows);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
