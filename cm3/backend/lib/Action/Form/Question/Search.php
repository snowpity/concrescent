<?php

namespace CM3_Lib\Action\Form\Question;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\forms\question;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $whereParts = array(
            new SearchTerm('event_id', $request->getAttribute('event_id')),
            new SearchTerm('context_code', $params['context_code']),
          //new SearchTerm('active', 1)
        );

        $order = array('order' => false);


        // Invoke the Domain with inputs and retain the result
        $data = $this->question->Search(array(
            'id','order','title','text','type','values','listed','visible_condition'
        ), $whereParts, $order);

        //Patch values to be an array
        array_walk($data, function (&$entry) {
            $entry['values'] = array_map(function ($value) {
                return trim($value);
            }, explode("\n", $entry['values']));
        });

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
