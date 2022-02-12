<?php

namespace CM3_Lib\Action\Application\Submission;

use CM3_Lib\models\application\submission;
use CM3_Lib\models\application\badgetype;
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
    public function __construct(private Responder $responder, private submission $submission, private badgetype $badgetype)
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
        $data = $this->submission->GetByID($params['id'], array('badge_type_id'));

        //Confirm the given badge_type_id belongs to the given group_id
        if (!$this->badgetype->verifyBadgeTypeBelongsToGroup($data['badge_type_id'], $params['group_id'])) {
            throw new HttpBadRequestException('Invalid badge_type_id specified');
        }


        // Invoke the Domain with inputs and retain the result
        $data = $this->submission->Delete($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
