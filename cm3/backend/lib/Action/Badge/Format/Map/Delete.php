<?php

namespace CM3_Lib\Action\Badge\Format\Map;

use CM3_Lib\models\badge\formatmap;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class Delete
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private format $format, private formatmap $formatmap)
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
        $event_id = $request->getAttribute('event_id');
        // Extract the form data from the request
        $data = array(
            'format_id'     => $params['format_id'],
            'category'      => $params['category'],
            'badge_type_id' => $params['badge_type_id'],
        );

        //Confirm the given format_id belongs to the given event_id
        if (!$this->format->verifyFormatBelongsToEvent($data['format_id'], $event_id)) {
            throw new HttpBadRequestException($request, 'Invalid format_id specified');
        }

        //Also confirm the specified badge_type_id belongs to the event id
        $badgetypemap = array(
            'Attendee'    => $this->a_badge_type,
            'Application' => $this->g_badge_type,
            'Staff'       => $this->s_badge_type,
        );
        if (!isset($badgetypemap[$data['category']])) {
            throw new HttpBadRequestException($request, 'Invalid badge context specified');
        }
        if (!($badgetypemap[$data['category']])->verifyBadgeTypeBelongsToEvent($data['badge_type_id'], $event_id)) {
            throw new HttpBadRequestException($request, 'Invalid badge id specified');
        }

        $data = $this->format->Delete($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
