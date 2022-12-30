<?php

namespace CM3_Lib\Action\System\ErrorLog;

use CM3_Lib\models\admin\error_log;
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
    public function __construct(private Responder $responder, private error_log $error_log)
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
        //Ensure consistency with the enpoint being posted to
        $data['id'] = $params['id'];
        $data['group_id'] = $request->getAttribute('group_id');
        $data['display_order'] = $data['display_order'] ?? 0;
        unset($data['date_created']);
        unset($data['date_modified']);
        unset($data['dates_available']);

        if (empty($data['start_date'])) {
            unset($data['start_date']);
        }
        if (empty($data['end_date'])) {
            unset($data['end_date']);
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->error_log->Update($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
