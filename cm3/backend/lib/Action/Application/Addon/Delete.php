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
        $data =array(
            'id' => $params['id']
        );

        if (!$this->addon->verifyAddonBelongsToGroup($params['id'], $request->getAttribute('group_id'))) {
            throw new HttpBadRequestException($request, 'Addon does not belong to current event');
        }

        $data = $this->addon->Update(array('id'=>$params['id'],'active'=>0));
        $this->addonmap->setBadgeTypesForAddon($params['id'], []);

        // We don't delete, just deactivate
        //$data = $this->printjob->Delete(array('id'=>$params['id']));

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
