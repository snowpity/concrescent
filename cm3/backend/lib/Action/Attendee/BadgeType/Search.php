<?php

namespace CM3_Lib\Action\Attendee\BadgeType;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\attendee\badgetype;
use CM3_Lib\models\attendee\badge;

use CM3_Lib\util\badgeinfo;

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
        private badgetype $badgetype,
        private badgeinfo $badgeinfo,
        private badge $badge
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
        $qp = $request->getQueryParams();
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $whereParts = array(
          new SearchTerm('event_id', $request->getAttribute('event_id'))
        );



        $pg = $this->badgeinfo->parseQueryParamsPagination($qp, 'display_order');
        $totalRows = 0;
        // Invoke the Domain with inputs and retain the result
        $data = $this->badgetype->Search( new View(
            array(
              'id',
              'display_order',
              'name',
              'price',
              'quantity',
              'start_date',
              'end_date',
              'min_age',
              'max_age',
              'dates_available',
              new SelectColumn('quantity_sold', EncapsulationFunction: 'ifnull(?,0)', Alias: 'quantity_sold', JoinedTableAlias: 'q'),
              new SelectColumn('quantity_sold', EncapsulationFunction: 'quantity - ifnull(?,0)', Alias: 'quantity_remaining', JoinedTableAlias: 'q')
          ),
            array(
            new Join(
                $this->badge,
                array(
                  'badge_type_id'=>'id',
                ),
                'LEFT',
                'q',
                array(
                  new SelectColumn('badge_type_id', true),
                  new SelectColumn('id', false, 'count(?)', 'quantity_sold')
              ),
                array(
                 new SearchTerm('payment_status', 'Completed'),
               )
            ),
          )
        ), $whereParts, $pg['order'], $pg['limit'], $pg['offset'], $totalRows);

        $response = $response->withHeader('X-Total-Rows', (string)$totalRows);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
