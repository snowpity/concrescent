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
final class FinishCheckIn
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
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        $bid = $params['badge_id'];
        $cx = $params['context_code'];
        $this->badgeinfo->SetEventId($request->getAttribute('event_id'));

        $current = $this->badgeinfo->GetSpecificBadge($bid, $cx, true);

        if ($current === false) {
            throw new HttpNotFoundException($request);
        }
        if ($current['payment_status'] != 'Completed') {
            throw new HttpBadRequestException($request, 'Badge is not in an allowed state!');
        }

        $result = array();

        $didUpdate = $this->badgeinfo->UpdateSpecificBadgeUnchecked(
            $bid,
            $cx,
            $data,
            array(
                'real_name',
                'fandom_name',
                'name_on_badge',
                'date_of_birth',
                'notes'
            )
        );

        if ($didUpdate > 0 || $current['time_printed'] == null) {
            //TODO: Check banlist...

            //We need to (re)print now!
            //TODO:Implement
        }

        $result['time_checked_in'] = date(DATE_RFC2822);

        //Finalize their checkin
        $didUpdate = $this->badgeinfo->UpdateSpecificBadgeUnchecked(
            $bid,
            $cx,
            array(
                'time_checked_in'=>$result['time_checked_in']
            )
        );

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
