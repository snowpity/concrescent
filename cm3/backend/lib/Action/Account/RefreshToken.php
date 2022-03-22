<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\util\TokenGenerator;
use CM3_Lib\util\CurrentUserInfo;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RefreshToken
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private TokenGenerator $TokenGenerator,
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
        $result = $this->TokenGenerator->forUser(
            $this->CurrentUserInfo->GetContactId(),
            $this->CurrentUserInfo->GetEventId()
        );

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
