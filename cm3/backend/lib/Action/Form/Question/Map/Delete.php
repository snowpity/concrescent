<?php

namespace CM3_Lib\Action\Form\Question\Map;

use CM3_Lib\models\forms\questionmap;
use CM3_Lib\models\forms\question;
use CM3_Lib\util\badgeinfo as badgeinfo;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class Delete
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private questionmap $questionmap,
        private question $question,
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
        $event_id = $request->getAttribute('event_id');
        //Confirm the given badge_type_id belongs to the given event_id
        if (!$this->badgeinfo->checkBadgeTypeBelongsToEvent($params['context_code'], $params['badge_type_id'])) {
            throw new HttpBadRequestException($request, 'Invalid context_code/badge_type_id specified');
        }
        // Extract the form data from the request
        $data = array(
            'question_id'     => $params['question_id'],
            'context_code'      => $params['context_code'],
            'badge_type_id' => $params['badge_type_id'],
        );

        //Confirm the given question_id belongs to the given event_id
        if (!$this->question->verifyQuestionBelongsToEvent($data['question_id'], $event_id)) {
            throw new HttpBadRequestException($request, 'Invalid question_id specified');
        }
        $data = $this->questionmap->Delete($data);

        if ($data == 1) {
            // Build the HTTP response
            return $response;
        }
        throw new HttpNotFoundException($request);
    }
}
