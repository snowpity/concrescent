<?php

namespace CM3_Lib\Action\AdminUser;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\admin\user;
use CM3_Lib\Responder\Responder;
use CM3_Lib\util\PermEvent;
use CM3_Lib\util\PermGroup;
use CM3_Lib\util\EventPermissions;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class GetPerms
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
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
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        // Invoke the Domain with inputs and retain the result
        $data = [
            'EventPerms'=> (new PermEvent(0))->keys(),
            'GroupPerms'=> (new PermGroup(0))->keys()
        ];

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
