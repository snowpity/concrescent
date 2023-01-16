<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\util\CurrentUserInfo;
use CM3_Lib\util\badgeinfo;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetMyApplications
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private CurrentUserInfo $CurrentUserInfo,
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
        //Fetch the authenticated user's info
        $c_id = $this->CurrentUserInfo->GetContactId();
        $e_id = $this->CurrentUserInfo->GetEventId();
        $searchTerms = array(
          new SearchTerm('contact_id', $c_id),
          new SearchTerm('payment_status', ['Completed','Incomplete'], 'IN'),

        );

        $result = $this->badgeinfo->SearchGroupApplications(null, $searchTerms, full: true);


        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
