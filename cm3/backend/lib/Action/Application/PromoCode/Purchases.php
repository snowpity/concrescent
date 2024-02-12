<?php

namespace CM3_Lib\Action\Application\PromoCode;

use CM3_Lib\models\application\promocode;
use CM3_Lib\models\application\submission;
use CM3_Lib\models\application\badgetype;

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
final class Purchases
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private promocode $promocode,
        private submission $submission,
        private badgetype $badgetype,
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

        $promo = $this->promocode->GetByID($params['id'], ['event_id', 'code']);

        if ($promo === false) {
            throw new HttpBadRequestException($request, 'Promo not found');
        }
        if ($promo['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Promo not found.');
        }

        //Grab badge types for the given context code
        $btids = $this->badgeinfo->GetValidTypeIdsForContext($params['context_code']);


        $whereParts = array(
          new SearchTerm('payment_promo_code', $promo['code']),
          new SearchTerm('payment_promo_code', $btids,'IN'),
        );

        $qp = $request->getQueryParams();

        $pg = $this->badgeinfo->parseQueryParamsPagination($qp, 'id', defaultSortDesc:true);
        $totalRows = 0;


        // Invoke the Domain with inputs and retain the result
        $data = $this->submission->Search(new View(
            array(
                'id',
                'payment_status',
                'payment_id',
                'contact_id',
                'display_id',
                'real_name',
                'fandom_name',
                'name_on_badge',
                new SelectColumn('name', Alias:'badge_type_name', JoinedTableAlias:'typ'),
                'application_status',
                new SelectColumn('context_code', EncapsulationFunction: "'".$params['context_code']."'", Alias:'context_code'),
            ),
            array(
                new Join(
                    $this->badgetype,
                    array(
                      'id' => 'badge_type_id'
                    ),
                    alias:'typ'
                ),
            )
        ), $whereParts, $pg['order'], $pg['limit'], $pg['offset'], $totalRows);

        $response = $response->withHeader('X-Total-Rows', (string)$totalRows);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
