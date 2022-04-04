<?php

namespace CM3_Lib\Action\Form\Response;

use CM3_Lib\models\forms\response;
use CM3_Lib\models\forms\question;
use CM3_Lib\models\forms\questionmap;
use CM3_Lib\models\attendee\badge as a_badge;
use CM3_Lib\models\application\submissionapplicant as g_badge;
use CM3_Lib\models\staff\badge as s_badge;

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
        private response $response,
        private question $question,
        private questionmap $questionmap,
        private a_badge $a_badge,
        private s_badge $s_badge,
        private g_badge $g_badge
    )
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


        //Get the badge table
        switch ($params['context_code']) {
            case 'A':   $badge = $this->a_badge; break;
            case 'S':   $badge = $this->s_badge; break;
            default:    $badge = $this->g_badge; break;
        }

        //Confirm permission to add to this question
        $questioninfo = $this->question->GetByID($params['question_id'], new View(
            array('id'),
            array(
                new Join($this->questionmap, array(
                    new SearchTerm('context_code', $params['context_code']),
                    'question_id'=>'id',
                ), alias: 'qm'),
                new Join($badge, array(
                    'badge_type_id' => new SearchTerm('badge_type_id', null, JoinedTableAlias: 'qm'),
                    new SearchTerm('id', $params['context_id'])
                ))
            )
        ));

        if ($questioninfo === false) {
            throw new HttpBadRequestException($request, 'Invalid question specified');
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->response->GetByID(array(
            'badge_type_id' => $params['badge_type_id'],
            'context_code' => $params['context_code'],
            'question_id' => $params['question_id']
        ), '*');

        if ($data === false) {
            throw new HttpNotFoundException($request);
        }


        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
