<?php

namespace CM3_Lib\Action\Application\SubmissionApplicant;

use CM3_Lib\util\badgeinfo;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

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
    public function __construct(private Responder $responder, private badgeinfo $badgeinfo)
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

        $qp = $request->getQueryParams();
        $find = $qp['find'] ?? '';
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $pg = $this->badgeinfo->parseQueryParamsPagination($qp, defaultSortDesc:true);
        $totalRows = 0;

        //Add in the questions requested
        $questionIds = array_filter(explode(',', $qp['questions']??''), function ($v) {
            return !empty($v);
        });
        // Invoke the Domain with inputs and retain the result
        $data = $this->badgeinfo->SearchBadgesText($params['context_code'], $find, $pg['order'], $pg['limit'], $pg['offset'], $totalRows, $questionIds);

        $response = $response->withHeader('X-Total-Rows', (string)$totalRows);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
