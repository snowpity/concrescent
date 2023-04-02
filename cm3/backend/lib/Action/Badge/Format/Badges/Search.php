<?php

namespace CM3_Lib\Action\Badge\Format\Badges;

use CM3_Lib\util\badgeinfo;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\badge\format;
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
final class Search
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
        private badgeinfo $badgeinfo
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
        $qp = $request->getQueryParams();


        $result = $this->format->Exists($params['format_id']);
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }
        if (!$this->format->verifyFormatBelongsToEvent($params['format_id'], $request->getAttribute('event_id'))) {
            throw new HttpBadRequestException($request, 'Badge Format does not belong to the current event!');
        }


        $pg = $this->badgeinfo->parseQueryParamsPagination($qp, 'id');

        $applicableBadgeTypes = $this->formatmap->Search(array(), array(
            new SearchTerm('format_id', $params['format_id'])
        ));

        //Generate the Where lists
        $applicableBadgeTypesByContext = array_reduce($applicableBadgeTypes, function (array $accumulator, array $element) {
            $accumulator[$element['context_code']][] = $element['badge_type_id'];
            return $accumulator;
        }, []);

        $searchTerms = array_map(function ($context_code, $badge_type_ids) {
            return new SearchTerm('', '', TermType:'OR', subSearch: array(
                new SearchTerm('context_code', $context_code, JoinedTableAlias:'grp'),
                new SearchTerm('id', $badge_type_ids, 'IN', JoinedTableAlias:'typ')
            ));
        }, array_keys($applicableBadgeTypesByContext), array_values($applicableBadgeTypesByContext));

        $totalRows = 0;
        $data = $this->badgeinfo->SearchBadges(false, $searchTerms, $pg['order'], $pg['limit'], $pg['offset'], $totalRows);


        $response = $response->withHeader('X-Total-Rows', (string)$totalRows);
        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
