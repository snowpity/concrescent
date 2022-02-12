<?php

namespace CM3_Lib\Action\Application\Submission;

use CM3_Lib\models\application\submission;
use CM3_Lib\models\application\badgetype;

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
        $data = (array)$request->getParsedBody();
        //TODO: Actually do something with submitted data. Also, provide some sane defaults


        // Invoke the Domain with inputs and retain the result
        $data = $this->submission->GetByID($params['id'], new View(
            array(),
            array(new Join($this->badgetype, array('id'=>'badge_type_id', new SearchTerm('group_id', $params['group_id']))))
        ));

        if ($data === false) {
            throw new HttpNotFoundException($request);
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
