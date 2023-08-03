<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\eventinfo;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class ListEventInfo
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private eventinfo $eventinfo)
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $whereParts = array(
          new SearchTerm('active', 1)
        );

        $order = array('date_end' => false);

        $page      = ($request->getQueryParams()['page']?? 0 > 0) ? $request->getQueryParams()['page'] : 1;
        $limit     = $request->getQueryParams()['itemsPerPage']?? -1; // Number of posts on one page
        $offset      = ($page - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }


        // Invoke the Domain with inputs and retain the result
        $data = $this->eventinfo->Search(array(), $whereParts, $order, $limit, $offset);

        usort($data, array($this,'compareEvents'));

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }

    //Courtesy ChatGPT
    public function compareEvents($a, $b)
    {
        $now = date("Y-m-d");
        $a_start = strtotime($a["date_start"]);
        $a_end = strtotime($a["date_end"]);
        $b_start = strtotime($b["date_start"]);
        $b_end = strtotime($b["date_end"]);

        if ($a_start <= strtotime($now) && $a_end >= strtotime($now)) {
            return -1; // $a is in progress
        } elseif ($b_start <= strtotime($now) && $b_end >= strtotime($now)) {
            return 1; // $b is in progress
        } elseif ($a_end >= strtotime($now) && $b_end >= strtotime($now)) {
            return $a_start - $b_start; // both $a and $b are in the future
        } else {
            return $b_end - $a_end; // both $a and $b are in the past
        }
    }
}
