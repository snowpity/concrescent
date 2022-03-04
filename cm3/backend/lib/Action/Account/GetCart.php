<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\payment;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetCart
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private payment $payment)
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
        $data = (array)$request->getParsedBody();

        //Fetch the authenticated user's info
        $c_id = $request->getAttribute('contact_id');
        $e_id = $request->getAttribute('event_id');
        $searchTerms = array(
          new SearchTerm('event_id', $e_id),
          new SearchTerm('contact_id', $c_id),
        );

        //Do we want the non-in-progress ones?
        if (()$data['include_inactive'] ?? false) != true) {
            $searchTerms[] = new SearchTerm('payment_status', array('NotStarted','Incomplete'), 'IN');
        }

        //Simply get the user's active Payments
        $result = $this->payment->Search(
            array(
                'id','uuid',
                'requested_by',
                'payment_system',
                'items',
                'payment_status'
            ),
            $searchTerms
        );

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
