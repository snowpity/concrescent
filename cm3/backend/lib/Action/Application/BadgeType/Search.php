<?php

namespace CM3_Lib\Action\Application\BadgeType;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\Join;
use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\View;
use CM3_Lib\models\application\badgetype;
use CM3_Lib\models\application\submission;
use CM3_Lib\models\application\group;

use CM3_Lib\util\CurrentUserInfo;

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
        private submission $submission,
        private group $group,
        private CurrentUserInfo $CurrentUserInfo,
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
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $order = array('display_order' => false);

        $page      = ($request->getQueryParams()['page']?? 0 > 0) ? $request->getQueryParams()['page'] : 1;
        $limit     = $request->getQueryParams()['itemsPerPage']?? -1; // Number of posts on one page
        $offset      = ($page - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->badgetype->Search(new View([
            'id','name','price','quantity','base_applicant_count','dates_available',
            new SelectColumn('quantity_sold', EncapsulationFunction: 'ifnull(?,0)', Alias: 'quantity_sold', JoinedTableAlias: 'q'),
            new SelectColumn('quantity_sold', EncapsulationFunction: 'quantity - ifnull(?,0)', Alias: 'quantity_remaining', JoinedTableAlias: 'q')

        ], [

            new Join(
                $this->submission,
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
                 new SearchTerm('application_status', ['PendingAcceptance','Accepted'], "IN"),
               )
            ),
           new Join(
               $this->group,
               array(
                 'id' => new SearchTerm('group_id', null),
                 new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId()),
                 new SearchTerm('context_code', $params['context_code'])
               ),
               alias:'grp'
           )
       ]), [], $order, $limit, $offset);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
