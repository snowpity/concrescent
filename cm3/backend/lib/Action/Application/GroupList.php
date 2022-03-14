<?php

namespace CM3_Lib\Action\Application;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\application\group;
use CM3_Lib\util\CurrentUserInfo;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class GroupList
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private group $group,
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $perms = $request->getAttribute('perms');
        $selectColumns = array(
            'id',
            'context_code',
            'active',
            'can_assign_slot',
            'display_order',
            'name',
            'menu_icon',
            'description',
            'application_name1',
            'application_name1'
        );

        $whereParts = array( new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId()));
        if (!($perms->EventPerms->isEventAdmin() || $perms->EventPerms->isGlobalAdmin())) {
            $whereParts[] = new SearchTerm('id', array_keys($perms->GroupPerms), 'IN');
        }

        $order = array('display_order' => false);

        // Invoke the Domain with inputs and retain the result
        $data = $this->group->Search($selectColumns, $whereParts, $order);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
