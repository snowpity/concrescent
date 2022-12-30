<?php

namespace CM3_Lib\Action\System\ErrorLog;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\Join;
use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\View;
use CM3_Lib\models\admin\error_log;
use CM3_Lib\models\contact;

use CM3_Lib\util\CurrentUserInfo;
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
        private error_log $error_log,
        private contact $contact,
        private badgeinfo $badgeinfo,
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
        $qp = $request->getQueryParams();
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $whereParts = [
            $this->CurrentUserInfo->EventIdSearchTerm()
        ];

        $searchView = new View([
            'id','timestamp','remote_addr','request_uri','message',
            new SelectColumn('real_name', Alias:'real_name', EncapsulationFunction: 'IFNULL(?,\'Anonymous\')', JoinedTableAlias:'c')
        ], [

           new Join(
               $this->contact,
               array(
                 'id' => new SearchTerm('contact_id', null)

             ),
               'LEFT',
               alias:'c'
           )
       ]);

        $pg = $this->badgeinfo->parseQueryParamsPagination($qp, 'id', true);
        $totalRows = 0;

        // Invoke the Domain with inputs and retain the result
        $data = $this->error_log->Search($searchView, $whereParts, $pg['order'], $pg['limit'], $pg['offset'], $totalRows);

        $response = $response->withHeader('X-Total-Rows', (string)$totalRows);
        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
