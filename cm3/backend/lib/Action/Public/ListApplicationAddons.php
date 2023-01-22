<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\application\badgetype;
use CM3_Lib\models\application\addon;
use CM3_Lib\models\application\addonmap;
use CM3_Lib\models\application\addonpurchase;

use CM3_Lib\util\CurrentUserInfo;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class ListApplicationAddons
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private badgetype $badgetype, private addon $addon, private addonmap $addonmap, private addonpurchase $addonpurchase, private CurrentUserInfo $CurrentUserInfo)
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
                  new SelectColumn('id', true, 'count(?)', 'quantity_sold')
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
                'a',
                array(
                    'addon_id'
                ),
                array(
                 new SearchTerm('badge_type_id', $params['badge_id']),
               )
            )
          )
        );

        $whereParts = array(
          new SearchTerm('event_id', $params['event_id']),
          new SearchTerm('', '', subSearch:array(
              new SearchTerm('active', 1),
              new SearchTerm('active_override_code', $override, TermType:'OR'),
          ))
        );

        $order = array('display_order' => false);


        // Invoke the Domain with inputs and retain the result
        $data = $this->badgetype->Search($viewData, $whereParts, $order);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
