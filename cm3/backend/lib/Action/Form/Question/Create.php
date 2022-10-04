<?php

namespace CM3_Lib\Action\Form\Question;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\SelectColumn;

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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $params): ResponseInterface
    {
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();

        //Ensure we're only attempting to create a question for the current Event
        $data['event_id'] = $request->getAttribute('event_id');
        $data['context_code'] = $params['context_code'];
        $data['active'] = 1;
        unset($data['id']);

        //Determine the last point to add
        $data['order'] = 1;
        $orderList = $this->question->Search(array(
            new SelectColumn('order', EncapsulationFunction: 'max(?) + 1', Alias:'order'),
        ), array(
            new SearchTerm('event_id', $data['event_id']),
            new SearchTerm('context_code', $data['context_code']),
        ), limit:1);
        if (count($orderList) > 0 && !empty($orderList[0]['order'])) {
            $data['order'] = $orderList[0]['order'];
        } else {
            $data['order'] = 1;
        }

        //TODO: Validate group context validity
        //combine the values back to a string
        if (is_array($data['values'])) {
            $data['values'] = implode("\n", $data['values']);
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->question->Create($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
