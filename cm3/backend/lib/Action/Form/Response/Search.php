<?php

namespace CM3_Lib\Action\Form\Response;

use CM3_Lib\models\forms\response;
use CM3_Lib\models\forms\question;
use CM3_Lib\models\forms\questionmap;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

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
    public function __construct(private Responder $responder, private response $response, private question $question, private questionmap $questionmap)
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

        //TODO: Make the check efficient?
        // //Confirm permission to read this question response
        // $questioninfo = $this->question->GetByID($params['question_id'], new View(
        //     array('id'),
        //     array(
        //         new Join($this->questionmap, array(
        //             new SearchTerm('context', $params['context']),
        //             new SearchTerm('badge_type_id', $params['badge_type_id']),
        //             'question_id'=>'id',
        //         )),
        //     )
        // ));
        //
        // if ($questioninfo === false) {
        //     throw new HttpBadRequestException($request, 'Invalid question specified');
        // }


        $whereParts = array(
            new SearchTerm('context', $params['context']),
            new SearchTerm('context_id', $params['context_id']),
        );

        $order = array('id' => false);

        $page      = ($request->getQueryParams()['page']?? 0 > 0) ? $request->getQueryParams()['page'] : 1;
        $limit     = $request->getQueryParams()['itemsPerPage']?? -1; // Number of posts on one page
        $offset      = ($page - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->response->Search(array(), $whereParts, $order, $limit, $offset);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
