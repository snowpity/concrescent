<?php

namespace CM3_Lib\Action\Application\Addon;

use CM3_Lib\models\application\addon;
use CM3_Lib\models\application\addonmap;
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
    public function __construct(
        private Responder $responder,
        private addon $addon,
        private addonmap $addonmap
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

        //Ensure we're making a addon with the associated event
        $data['group_id'] = $request->getAttribute('group_id');
        unset($data['id']);
        $data['display_order'] = 0;
        unset($data['date_created']);
        unset($data['date_modified']);
        unset($data['dates_available']);

        if (empty($data['start_date'])) {
            unset($data['start_date']);
        }
        if (empty($data['end_date'])) {
            unset($data['end_date']);
        }

        if (isset($data['valid_badge_type_ids'])) {
            $btIDs = $data['valid_badge_type_ids'];
            if (is_string($btIDs)) {
                $btIDs = explode(',', $btIDs);
            }
        }
        // Invoke the Domain with inputs and retain the result
        $data = $this->addon->Create($data);


        if (isset($btIDs)) {
            $this->addonmap->setBadgeTypesForAddon($data['id'], $btIDs);
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
