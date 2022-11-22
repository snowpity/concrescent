<?php

namespace CM3_Lib\Action\Attendee\PromoCode;

use CM3_Lib\models\attendee\promocode;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class Update
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private promocode $promocode)
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
        // Only soft deletes
        $data =array(
            'id' => $params['id']
            'active' => 0
        );

        if ($this->promocode->GetByID($params['id'],  array('event_id')) != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'PromoCode does not belong to current event');
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->promocode->Update($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
