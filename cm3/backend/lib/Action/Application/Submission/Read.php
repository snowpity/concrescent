<?php

namespace CM3_Lib\Action\Application\Submission;

use CM3_Lib\models\application\group;
use CM3_Lib\models\application\badgetype;
use CM3_Lib\util\badgeinfo;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class Read
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
        private badgetype $badgetype,
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
        //Fetch the context code of the group specified
        $group = $this->group->GetByID($params['group_id'], []);

        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $result = $this->badgeinfo->GetSpecificBadge($params['id'], $group['context_code'], full:true);

        // // Invoke the Domain with inputs and retain the result
        // $result = $this->badge->GetByID($params['id'], '*');
        //
        //Confirm badge belongs to a badgetype in this event
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }
        if (!$this->badgeinfo->checkBadgeTypeBelongsToEvent($group['context_code'], $result['badge_type_id'])) {
            throw new HttpNotFoundException($request);
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
