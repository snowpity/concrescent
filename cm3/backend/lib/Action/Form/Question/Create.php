<?php

namespace CM3_Lib\Action\Form\Question;

use CM3_Lib\models\forms\question;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
    public function __construct(private Responder $responder, private question $question)
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();

        //Ensure we're only attempting to create a question for the current Event
        $data['event_id'] = $request->getAttribute('event_id');

        //TODO: Validate group context validity

        // Invoke the Domain with inputs and retain the result
        $data = $this->question->Create($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
