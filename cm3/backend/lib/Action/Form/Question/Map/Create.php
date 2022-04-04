<?php

namespace CM3_Lib\Action\Form\Question\Map;

use CM3_Lib\models\forms\questionmap;
use CM3_Lib\models\forms\question;
use CM3_Lib\models\attendee\badgetype as a_badge_type;
use CM3_Lib\models\application\badgetype as g_badge_type;
use CM3_Lib\models\staff\badgetype as s_badge_type;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class Create
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
        private a_badge_type $a_badge_type,
        private g_badge_type $g_badge_type,
        private s_badge_type $s_badge_type,
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
        $event_id = $request->getAttribute('event_id');
        // Extract the form data from the request
        $data =array_merge($data, array(
            'context_code'       => $params['context_code'],
            'badge_type_id' => $params['badge_type_id'],
            'question_id'   => $params['question_id'],
        ));

        //Confirm the given question_id belongs to the given event_id
        if (!$this->question->verifyQuestionBelongsToEvent($data['question_id'], $event_id)) {
            throw new HttpBadRequestException($request, 'Invalid question_id specified');
        }

        //Also confirm the specified badge_type_id belongs to the event id
        switch ($params['context_code']) {
            case 'A':
                $badge_type = $this->a_badge_type;
                break;
            case 'S':
                $badge_type = $this->s_badge_type;
                break;
            default:
                $badge_type = $this->g_badge_type;
                break;
        }
        // if (!isset($badgetypemap[$data['category']])) {
        //     throw new HttpBadRequestException($request, 'Invalid badge context specified');
        // }
        if (!$badge_type->verifyBadgeTypeBelongsToEvent($data['badge_type_id'], $event_id)) {
            throw new HttpBadRequestException($request, 'Invalid badge id specified');
        }

        if (!$this->questionmap->Exists($data)) {
            // Invoke the Domain with inputs and retain the result
            $data = $this->questionmap->Create($data);
        } else {
            $data = $this->questionmap->Update($data);
        }


        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
