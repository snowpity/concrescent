<?php

namespace CM3_Lib\Action\Application\PromoCode;

use CM3_Lib\models\application\promocode;
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
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();

        if (!$this->promocode->verifyPromoCodeBelongsToEvent($params['id'], $request->getAttribute('event_id'))) {
            throw new HttpBadRequestException($request, 'Badge does not belong to current event');
        }

        //Ensure consistency with the enpoint being posted to
        $data['id'] = $params['id'];
        unset($data['event_id']);
        unset($data['date_created']);
        unset($data['date_modified']);
        unset($data['dates_available']);

        if (empty($data['start_date'])) {
            $data['start_date'] = null;
        }
        if (empty($data['end_date'])) {
            $data['end_date'] = null;
        }


        // Invoke the Domain with inputs and retain the result
        $data = $this->promocode->Update($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
