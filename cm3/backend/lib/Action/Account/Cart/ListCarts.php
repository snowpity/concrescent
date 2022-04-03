<?php

namespace CM3_Lib\Action\Account\Cart;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\payment;
use CM3_Lib\util\CurrentUserInfo;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ListCarts
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private payment $payment,
        private CurrentUserInfo $CurrentUserInfo
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
        $data = (array)$request->getQueryParams();

        //Fetch the authenticated user's info
        $c_id = $this->CurrentUserInfo->GetContactId();
        $e_id = $this->CurrentUserInfo->GetEventId();
        $searchTerms = array(
          new SearchTerm('event_id', $e_id),
          new SearchTerm('contact_id', $c_id),
        );

        //Do we want the non-in-progress ones?
        if (($data['include_all'] ?? "false") == "false") {
            $searchTerms[] = new SearchTerm('payment_status', array('NotStarted','Incomplete'), 'IN');
        }

        //Simply get the user's active Payments
        $result = $this->payment->Search(
            array(
                'id','uuid',
                'requested_by',
                'payment_system',
                'payment_status',
                'date_modified'
            ),
            $searchTerms
        );

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
