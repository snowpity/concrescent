<?php

namespace CM3_Lib\Action\AdminUser;

use CM3_Lib\models\admin\user;
use CM3_Lib\Responder\Responder;
use CM3_Lib\util\PermEvent;
use CM3_Lib\util\TokenGenerator;
use CM3_Lib\util\CurrentUserInfo;
use CM3_Lib\util\UserPermissions;
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
        private user $user,
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
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        $data['contact_id'] = $params['id'];

        //Hash their password if they provided it
        if (isset($data['password'])) {
            $data['password'] =  password_hash($data['password'], PASSWORD_DEFAULT);
        }

        
        //If given a permissions object, convert it into a permissions string
        if (isset($data['permissions']) && !is_string($data['permissions'])) {
            //TODO: Make sure we can't escalate priviliges?

            $CurrentPermissions = $this->TokenGenerator->loadPermissions($data['contact_id']);
            //Convert to perms
            $data['permissions'] =
            $this->TokenGenerator->packPermissions(
                $this->TokenGenerator->mergePermsFromArray(
                    $CurrentPermissions,
                    $this->CurrentUserInfo->GetEventId(),
                    $data['permissions'],
                )
            );
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->user->Update($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
