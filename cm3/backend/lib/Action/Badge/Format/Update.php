<?php

namespace CM3_Lib\Action\Badge\Format;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\badge\format;
use CM3_Lib\models\badge\formatmap;
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
        private format $format,
        private formatmap $formatmap,
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
        $data['id'] = $params['id'];

        $result = $this->format->GetByID($params['id'], array('event_id'));
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }
        if ($result['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Badge Format does not belong to the current event!');
        }

        $data['layout'] = json_encode($data['layout']);

        // Invoke the Domain with inputs and retain the result
        $result = $this->format->Update($data);

        //If supplied with a badgeMap, save the format map
        if (isset($data['badgeMap'])) {

                //First fetch any that might exist already

            //Fetch the existing map
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

            foreach ($data['badgeMap'] as $context_code => $setBadges) {
                $existing = $badgeMap[$context_code] ?? [];
                //Create missing
                foreach (array_diff($setBadges, $existing) as $newBadge) {
                    $item = array(
                            'context_code' => $context_code,
                            'format_id' => $result['id'],
                            'badge_type_id' => $newBadge
                        );
                    $this->formatmap->create($item);
                }

                //Delete the missing ones
                foreach (array_diff($existing, $setBadges) as $goneBadge) {
                    $item = array(
                        'context_code' => $context_code,
                        'format_id' => $result['id'],
                        'badge_type_id' => $goneBadge
                    );
                    $this->formatmap->Delete($item);
                }
            }
        }



        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
