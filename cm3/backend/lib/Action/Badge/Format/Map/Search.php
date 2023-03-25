<?php

namespace CM3_Lib\Action\Badge\Format\Map;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\badge\format;
use CM3_Lib\models\badge\formatmap;
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
        $qp = $request->getQueryParams();
        $viewData = new View(
            array(
            "id",
            "name",
            "bgImageID",
            "customSize",
            "layoutPosition",
            isset($qp['full']) && $qp['full'] == 'true' ?
                "layout" : null,
            ),
            array(
            new Join(
                $this->formatmap,
                array(
                  'format_id'=>'id',
                ),
                'INNER',
                'f',
                array(
                      'format_id',
                  ),
                array(
                  new SearchTerm('context_code', $params['context_code']),
                  new SearchTerm('badge_type_id', $params['badge_type_id']),
               )
            ),
          )
        );

        $whereParts = array(
            new SearchTerm('event_id', $request->getAttribute('event_id'))
        );

        $order = array('id' => false);


        // Invoke the Domain with inputs and retain the result
        $data = $this->format->Search($viewData, $whereParts, $order);

        foreach ($data as &$value) {
            //Move into raw for safe-keeping
            $value['layout_raw'] = $value['layout'];
            $value['layout'] = json_decode($value['layout']);
            if (0==json_last_error()) {
                unset($value['layout_raw']);
            }
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
