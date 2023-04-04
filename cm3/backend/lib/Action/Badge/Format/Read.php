<?php

namespace CM3_Lib\Action\Badge\Format;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\badge\format;
use CM3_Lib\models\badge\formatmap;
use CM3_Lib\models\application\group;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class Read
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private format $format,
        private formatmap $formatmap,
        private group $group,
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
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $result = $this->format->GetByID($params['id'], '*');
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }
        if ($result['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Badge Format does not belong to the current event!');
        }

        //Convert layout to array
        $lRaw = $result['layout'];
        $result['layout'] = json_decode($result['layout']);
        if (json_last_error()) {
            $result['layout_raw'] = $lRaw;
        }

        //Fetch the map
        $formatMap = $this->formatmap->Search(['context_code','badge_type_id'], [
            new SearchTerm('format_id', $params['id'])
        ]);

        $badgeMap = [];
        //Bin into contexts
        if ($formatMap !== false) {
            foreach ($formatMap as $map) {
                if (!array_key_exists($map['context_code'], $badgeMap)) {
                    $badgeMap[$map['context_code']] = [];
                }
                $badgeMap[$map['context_code']][] = $map['badge_type_id'];
            }
        }

        //Ensure all contexts are represented
        $context_codes = $this->group->Search(array(
           'context_code',
         ), array(new SearchTerm('event_id', $result['event_id'])));

        //Append the hard-coded contexts
        $context_codes[] = array('context_code'=>'A');
        $context_codes[] = array('context_code'=>'S');

        foreach (array_diff_key(array_flip(array_column($context_codes, 'context_code')), $badgeMap) as $context_code => $value) {
            $badgeMap[$context_code] = [];
        }


        $result['badgeMap'] = $badgeMap;

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
