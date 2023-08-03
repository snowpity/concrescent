<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\attendee\badgetype;
use CM3_Lib\models\attendee\addon;
use CM3_Lib\models\attendee\addonmap;
use CM3_Lib\models\attendee\addonpurchase;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class ListAllAttendeeAddons
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private badgetype $badgetype, private addon $addon, private addonmap $addonmap, private addonpurchase $addonpurchase)
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
        $qp = $request->getQueryParams();
        $override = $qp['override'] ?? null;
        $viewData = new View(
            array(
                new SelectColumn('badge_type_id', JoinedTableAlias:'am'),
              'id',
              'display_order',
              'name',
              'description',
              'rewards',
              'price',
              'payable_onsite',
              'quantity',
              'start_date',
              'end_date',
              'min_age',
              'max_age',
              'dates_available',
              new SelectColumn('quantity_sold', EncapsulationFunction: 'ifnull(?,0)', Alias: 'quantity_sold', JoinedTableAlias: 'q'),
              new SelectColumn('quantity_sold', EncapsulationFunction: 'quantity - ?', Alias: 'quantity_remaining', JoinedTableAlias: 'q')
          ),
            array(
            new Join(
                $this->addonpurchase,
                array(
                  'addon_id'=>'id',
                ),
                'LEFT',
                'q',
                array(
                  'addon_id',
                  new SelectColumn('addon_id', true, 'count(?)', 'quantity_sold')
              ),
                array(
                 new SearchTerm('payment_status', 'Completed'),
               )
            ),
            new Join(
                $this->addonmap,
                array(
                  'addon_id'=>'id',
                ),
                'RIGHT',
                'am'
            ),
           new Join(
               $this->badgetype,
               array(
                 'id' => new SearchTerm('badge_type_id', '', JoinedTableAlias:'am'),
               ),
               'INNER',
               'bt',
               array('id'),
               array(
                   new SearchTerm('event_id', $params['event_id'])
               )
           )
          )
        );

        $whereParts = array(
          new SearchTerm('active', 1),
          new SearchTerm('active_override_code', $override, TermType:'OR'),
        );

        $order = array('display_order' => false);


        // Invoke the Domain with inputs and retain the result
        $data = $this->addon->Search($viewData, $whereParts, $order);

        //Post-process
        //Bring out the badge_type_id as the key to the data
        $newdata = array_fill_keys(array_unique(array_column($data, 'badge_type_id')), array());
        $removekeys = array_flip(array('badge_type_id'));
        array_walk($data, function (&$entry) use (&$newdata, $removekeys) {
            //Add it in
            $newdata[$entry['badge_type_id']][] = array_diff_key($entry, $removekeys);
        });

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $newdata);
    }
}
