<?php

namespace CM3_Lib\Action\Form\Question\Map;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\util\badgeinfo;
use CM3_Lib\models\forms\questionmap;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;

/**
 * Action.
 */
final class Search
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private badgeinfo $badgeinfo, private questionmap $questionmap)
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
        $event_id = $request->getAttribute('event_id');

        //Confirm the given badge_type_id belongs to the given event_id
        if (!$this->badgeinfo->checkBadgeTypeBelongsToEvent($params['context_code'], $params['badge_type_id'])) {
            throw new HttpBadRequestException($request, 'Invalid context_code/badge_type_id specified');
        }

        $whereParts = array(
            new SearchTerm('context_code', $params['context_code']),
            new SearchTerm('badge_type_id', $params['badge_type_id'])
          //new SearchTerm('active', 1)
        );
        // Invoke the Domain with inputs and retain the result
        $data = $this->questionmap->Search(array('question_id','required'), $whereParts);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
