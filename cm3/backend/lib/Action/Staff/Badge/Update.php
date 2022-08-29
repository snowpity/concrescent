<?php

namespace CM3_Lib\Action\Staff\Badge;

use CM3_Lib\models\staff;
use CM3_Lib\Responder\Responder;
use CM3_Lib\util\badgeinfo;
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

        // Invoke the Domain with inputs and retain the result
        $data = $this->badgeinfo->UpdateSpecificBadgeUnchecked($params['id'], 'S', $data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
