<?php

namespace CM3_Lib\Action\Account\Cart;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\payment;
use CM3_Lib\util\badgevalidator;
use CM3_Lib\util\CurrentUserInfo;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpNotFoundException;

class GetCart
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
        private badgevalidator $badgevalidator,
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
          new SearchTerm('id', $params['id']),
        );

        //Simply get the user's active Payments
        $result = $this->payment->Search(
            array(
                'id','uuid',
                'requested_by',
                'payment_system',
                'items',
                'payment_status',
                'date_created',
                'date_modified',
            ),
            $searchTerms
        );

        if ($result === false || (count($result)==0)) {
            throw new HttpNotFoundException($request);
        }

        //We should have only one
        $result = $result[0];

        //Decode the items
        $result['items'] = json_decode($result['items'], true);

        //Sift through and determine current errors
        $result['errors'] = array();
        foreach ($result['items'] as $badge) {
            $result['errors'][$badge['cartIx']] = $this->badgevalidator->ValdateCartBadge($badge);
        }

        $result['state'] = $result['payment_status'];

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
