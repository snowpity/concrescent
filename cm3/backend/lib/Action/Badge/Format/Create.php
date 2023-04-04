<?php

namespace CM3_Lib\Action\Badge\Format;

use CM3_Lib\models\badge\format;
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
    public function __construct(private Responder $responder, private format $format)
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

        //Ensure we're only attempting to create a format for the current Event
        $data['event_id'] = $request->getAttribute('event_id');

        $data['layout'] = json_encode($data['layout']);
        // Invoke the Domain with inputs and retain the result
        $data = $this->format->Create($data);


        //If supplied with a badgeMap, save the format map
        if (isset($data['badgeMap'])) {
            //This one is much simpler than the update since we know the map should not exist

            foreach ($data['badgeMap'] as $context_code => $setBadges) {
                //Create missing
                foreach ($setBadges as $newBadge) {
                    $item = array(
                        'context_code' => $context_code,
                        'format_id' => $result['id'],
                        'badge_type_id' => $newBadge
                    );
                    $this->formatmap->create($item);
                }
            }
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
