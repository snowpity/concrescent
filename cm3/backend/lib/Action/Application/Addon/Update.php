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
final class Update
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

        if (!$this->addon->verifyAddonBelongsToGroup($params['id'], $request->getAttribute('group_id'))) {
            throw new HttpBadRequestException($request, 'Addon does not belong to current event');
        }

        //Ensure consistency with the enpoint being posted to
        $data['id'] = $params['id'];
        unset($data['group_id']);
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
        $data = $this->addon->Update($data);

        if (isset($btIDs)) {
            $this->addonmap->setBadgeTypesForAddon($data['id'], $btIDs);
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
