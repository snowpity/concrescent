<?php

namespace CM3_Lib\Action\Application\Submission;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\application\group;
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
    public function __construct(private Responder $responder, private group $group, private badgeinfo $badgeinfo)
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
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        // //Grab the list of valid badge_type_ids
        // $badge_type_ids = array_column($this->badgetype->Search(
        //     array('id'),
        //     array(new SearchTerm('group_id', $params['group_id']))
        // ), 'id');
        // if (count($badge_type_ids) == 0) {
        //     $badge_type_ids = [0];
        // }
        //
        // $whereParts = array(
        //   new SearchTerm('badge_type_id', $badge_type_ids, 'IN')
        // );
        //
        // $order = array('id' => false);
        //
        // $page      = ($request->getQueryParams()['page']?? 0 > 0) ? $request->getQueryParams()['page'] : 1;
        // $limit     = $request->getQueryParams()['itemsPerPage']?? -1; // Number of posts on one page
        // $offset      = ($page - 1) * $limit;
        // if ($offset < 0) {
        //     $offset = 0;
        // }
        //
        // // Invoke the Domain with inputs and retain the result
        // $data = $this->submission->Search(array(), $whereParts, $order, $limit, $offset);
        $qp = $request->getQueryParams();
        $find = $qp['find'] ?? '';
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        // $matchedGroups = $this->group->Search(['id'], [
        //     $this->CurrentUserInfo->EventIdSearchTerm(),
        //     new SearchTerm('context_code', $context_code),
        // ]);
        // $group = $matchedGroups[0]['id'];

        $pg = $this->badgeinfo->parseQueryParamsPagination($qp, defaultSortDesc:true);
        $totalRows = 0;
        // Invoke the Domain with inputs and retain the result
        $data = $this->badgeinfo->SearchGroupApplicationsText($params['context_code'], $find, $pg['order'], $pg['limit'], $pg['offset'], $totalRows);


        $response = $response->withHeader('X-Total-Rows', (string)$totalRows);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
