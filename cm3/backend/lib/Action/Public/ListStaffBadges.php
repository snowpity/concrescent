<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\staff\badgetype;
use CM3_Lib\models\staff\badge;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class ListStaffBadges
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private badgetype $badgetype, private badge $badge)
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
        $viewData = new View(
            array(
              'id',
              'display_order',
              'name',
              'description',
              'price',
              'payable_onsite',
              'start_date',
              'end_date',
              'min_age',
              'max_age',
              'dates_available',
              new SelectColumn('quantity_sold', EncapsulationFunction: 'ifnull(?,0)', Alias: 'quantity_sold', JoinedTableAlias: 'q'),
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
                  'badge_type_id',
                  new SelectColumn('id', true, 'count(?)', 'quantity_sold')
              ),
                array(
                 new SearchTerm('application_status', 'Active'),
               )
            )
          )
        );

        $whereParts = array(
          new SearchTerm('active', 1)
        );

        $order = array('display_order' => false);


        // Invoke the Domain with inputs and retain the result
        $data = $this->badgetype->Search($viewData, $whereParts, $order);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
