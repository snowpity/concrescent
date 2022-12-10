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
final class Update
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $params): ResponseInterface
    {
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        $data['id'] = $params['id'];

        $result = $this->question->GetByID($params['id'], array('event_id'));
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }
        if ($result['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Form Question does not belong to the current event!');
        }

        //combine the values back to a string
        if (isset($data['values']) && is_array($data['values'])) {
            $data['values'] = implode("\n", $data['values']);
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->question->Update($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
