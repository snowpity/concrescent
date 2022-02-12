<?php

namespace CM3_Lib\Action\Application\SubmissionApplicant;

use CM3_Lib\models\application\submissionapplicant;
use CM3_Lib\models\application\submission;
use CM3_Lib\models\application\badgetype;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

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
    public function __construct(private Responder $responder, private submissionapplicant $submissionapplicant, private submission $submission, private badgetype $badgetype)
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

        //Confirm permission to read this submission applicant
        $submissioninfo = $this->submission->GetByID($params['application_id'], new View(
            array(
                new SelectColumn('id', JoinedTableAlias:'a')
            ),
            array(
                new Join($this->badgetype, array('id'=>'badge_type_id', new SearchTerm('group_id', $params['group_id']))),
                new Join($this->submissionapplicant, array('application_id'=>'id', new SearchTerm('id', $params['id'])), 'LEFT', 'a')
            )
        ));

        if ($submissioninfo === false) {
            throw new HttpBadRequestException($request, 'Invalid submission specified');
        }

        //Confirm the target entity belongs to the selected application

        $data['id'] = $params['id'];

        // Invoke the Domain with inputs and retain the result
        $data = $this->submissionapplicant->Update($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
