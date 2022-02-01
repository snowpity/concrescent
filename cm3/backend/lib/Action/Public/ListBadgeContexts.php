<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\application\group;
use CM3_Lib\models\application\badgetype;
use CM3_Lib\models\application\submission;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class ListBadgeContexts
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private group $group, private badgetype $badgetype, private submission $submission)
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


        $whereParts = array(
                  new SearchTerm('event_id', $params['event_id'])
                );

        $order = array('display_order' => false);


        // Invoke the Domain with inputs and retain the result
        $data = $this->group->Search(array(
          'context_code',
          'name'
          ), $whereParts, $order);

        //Append the hard-coded contexts
        $data[] = array('context_code'=>'A', 'name'=>'Attendee');
        $data[] = array('context_code'=>'S', 'name'=>'Staff');

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
