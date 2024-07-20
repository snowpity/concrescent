<?php

namespace CM3_Lib\Action\Badge\CheckIn;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\database\SearchTerm;


use CM3_Lib\util\badgeinfo;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

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
        //TODO: Also, provide some sane defaults
        $qp = $request->getQueryParams();
        $find = $qp['find'] ?? '';
        $context = $qp['context'] ?? false;
        $totalRows = 0;


        //First, determine if this is an exact match code, for example from QR code
        $badgeMatch = array();
        if (preg_match('/CM\*([a-zA-Z{1-5}])(\d{1,})\*([0-9A-Fa-f]{8}[-][0-9A-Fa-f]{4}[-][0-9A-Fa-f]{4}[-][0-9A-Fa-f]{4}[-][0-9A-Fa-f]{12})/m', $find, $badgeMatch)) {
            //Yep! Let's decode and check the validity
            $shortcode = $badgeMatch[1];
            $display_id =  $badgeMatch[2];
            $uuid =  $badgeMatch[3];

            $result = $this->badgeinfo->SearchSpecificBadge($uuid, $shortcode, false);

            if ($result !== false && $result['display_id'] == $display_id) {
                $totalRows = 1;
                $result = array($result);
            } else {
                //Check for group application's main QR code
                $result = $this->badgeinfo->SearchBadgesFromGroup($uuid,false,$totalRows);
            }
            
            $response = $response->withHeader('X-Total-Rows', (string)$totalRows);
            return $this->responder
                ->withJson($response, $result);
        }

        //Not a scanned badge. Let's search then...
        //Interpret order parameters
        $pg = $this->badgeinfo->parseQueryParamsPagination($qp, defaultSortDesc:true);
        $totalRows = 0;
        $data = $this->badgeinfo->SearchBadgesText($context, $find, $pg['order'], $pg['limit'], $pg['offset'], $totalRows);

        $response = $response->withHeader('X-Total-Rows', (string)$totalRows);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
