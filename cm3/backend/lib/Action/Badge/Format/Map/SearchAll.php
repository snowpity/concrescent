<?php

namespace CM3_Lib\Action\Badge\Format\Map;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\eventinfo;
use CM3_Lib\models\badge\format;
use CM3_Lib\models\badge\formatmap;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class SearchAll
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private eventinfo $eventinfo,
        private format $format,
        private formatmap $formatmap
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
        $qp = $request->getQueryParams();
        $viewData = new View(
            array(
                'badge_type_id',
                new SelectColumn('id', JoinedTableAlias:'f'),
                new SelectColumn('name', JoinedTableAlias:'f'),

            ),
            array(
            new Join(
                $this->format,
                array(
                  'id'=>'format_id',
                ),
                'INNER',
                'f',
                array(
                  'id',
                  'name'
              ),
                array(
                  new SearchTerm('event_id', $request->getAttribute('event_id'))
               )
            ),
          )
        );

        $whereParts = array(
          new SearchTerm('context_code', $params['context_code'])
        );

        $order = array('badge_type_id' => false);


        // Invoke the Domain with inputs and retain the result
        $data = $this->formatmap->Search($viewData, $whereParts, $order);

        //Post-process
        //Bring out the badge_type_id as the key to the data
        $newdata = array_fill_keys(array_unique(array_column($data, 'badge_type_id')), array());

        //If we're including the formats fully, fetch them
        $formats = [];
        if (isset($qp['full']) && $qp['full'] == 'true') {
            $formats = array_column($this->format->Search('*', [
                new SearchTerm(
                    'id',
                    array_unique(array_column($data, 'id')),
                    'IN'
                )
            ]), null, 'id');
            foreach ($formats as &$value) {
                //Move into raw for safe-keeping
                $value['layout_raw'] = $value['layout'];
                $value['layout'] = json_decode($value['layout']);
                if (0==json_last_error()) {
                    unset($value['layout_raw']);
                }
            }
        }


        $removekeys = array_flip(array('badge_type_id'));
        array_walk($data, function (&$entry) use (&$newdata, $removekeys, $formats) {
            //Add it in
            $newdata[$entry['badge_type_id']][] = array_diff_key($formats[$entry['id']]?? $entry, $removekeys);
        });

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $newdata);
    }
}
