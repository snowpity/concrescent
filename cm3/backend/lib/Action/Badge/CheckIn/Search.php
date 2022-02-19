<?php

namespace CM3_Lib\Action\Badge\CheckIn;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\attendee\badgetype as a_badge_type;
use CM3_Lib\models\application\badgetype as g_badge_type;
use CM3_Lib\models\staff\badgetype as s_badge_type;
use CM3_Lib\models\attendee\badge as a_badge;
use CM3_Lib\models\application\submission as g_badge_submission;
use CM3_Lib\models\application\submissionapplicant as g_badge;
use CM3_Lib\models\application\group as g_group;
use CM3_Lib\models\staff\badge as s_badge;
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
        private a_badge_type $a_badge_type,
        private g_badge_type $g_badge_type,
        private s_badge_type $s_badge_type,
        private a_badge $a_badge,
        private g_badge $g_badge,
        private s_badge $s_badge,
        private g_group $g_group,
        private g_badge_submission $g_badge_submission
    ) {
    }


    private $selectColumns = array(
      'id',
      'display_id',
      'real_name',
      'fandom_name',
      'name_on_badge',
      'date_of_birth',
      'notify_email',
      'time_printed',
      'time_checked_in'
    );
    private $event_id;

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
        //TODO: Also, provide some sane defaults
        $qp = $request->getQueryParams();
        $this->event_id =  $request->getAttribute('event_id');
        $find = $qp['find'];


        //First, determine if this is an exact match code, for example from QR code
        $badgeMatch = array();
        if (preg_match('/CM\*([a-zA-Z{1-5}])(\d{1,})\*([0-9A-Fa-f]{8}[-][0-9A-Fa-f]{4}[-][0-9A-Fa-f]{4}[-][0-9A-Fa-f]{4}[-][0-9A-Fa-f]{12})/m', $find, $badgeMatch)) {
            //Yep! Let's decode and check the validity
            $shortcode = $badgeMatch[1];
            $display_id =  $badgeMatch[2];
            $uuid =  $badgeMatch[3];

            switch ($shortcode) {
                case 'A':
                    $result = $this->getSpecificBadge($uuid, $this->a_badge, $this->a_badge_type, 'A');
                    break;
                case 'S':
                    $result = $this->getSpecificBadge($uuid, $this->s_badge, $this->s_badge_type, 'S');
                    break;
                default:
                    $result = $this->getSpecificGroupBadge($uuid);
                    break;
            }
            if ($result !== false && $result['display_id'] == $display_id) {
                $result = array($result);
            } else {
                $result = array();
            }

            return $this->responder
                ->withJson($response, array($result));
        }

        //Not a scanned badge. Let's search then...



        $whereParts = array(
            new SearchTerm('real_name', $find, Raw: 'MATCH(`real_name`, `fandom_name`, `notify_email`, `ice_name`, `ice_email_address`) AGAINST (? IN NATURAL LANGUAGE MODE) ')
          //new SearchTerm('active', 1)
        );

        $order = array('id' => false);

        $page      = ($qp['page']?? 0 > 0) ? $qp['page'] : 1;
        $limit     = $qp['itemsPerPage']?? -1; // Number of posts on one page
        $offset      = ($page - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }

        // Invoke the Domain with inputs and retain the result
        $a_data = $this->a_badge->Search($this->badgeView($this->a_badge_type, 'A'), $whereParts, $order, $limit, $offset);
        $s_data = $this->s_badge->Search($this->badgeView($this->s_badge_type, 'S'), $whereParts, $order, $limit, $offset);
        $g_data = $this->g_badge->Search($this->groupBadgeView(), $whereParts, $order, $limit, $offset);

        $data = array_merge($a_data, $s_data, $g_data);
        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }

    public function getSpecificBadge($uuid, $badge, $badgetype, $contextCode)
    {
        return $badge->GetByIDorUUID(
            null,
            $uuid,
            $this->badgeView($badgetype, $contextCode)
        );
    }
    public function badgeView($badgetype, $contextCode)
    {
        return new View(
            array_merge(
                $this->selectColumns,
                array(
               new SelectColumn('context_code', EncapsulationFunction: "'".$contextCode."'", Alias:'context_code'),
               new SelectColumn('application_status', EncapsulationFunction: "''", Alias:'application_status'),
               'badge_type_id',
               'payment_status',
               new SelectColumn('name', Alias:'badge_type_name', JoinedTableAlias:'typ')
             )
            ),
            array(

               new Join(
                   $badgetype,
                   array(
                     'id' => 'badge_type_id',
                     new SearchTerm('event_id', $this->event_id)
                   ),
                   alias:'typ'
               )
             )
        );
    }

    public function getSpecificGroupBadge($uuid)
    {
        return $this->g_badge->GetByIDorUUID(
            null,
            $uuid,
            $this->groupBadgeView()
        );
    }

    public function groupBadgeView()
    {
        return new View(
            array_merge(
                $this->selectColumns,
                array(
                   new SelectColumn('context_code', JoinedTableAlias:'grp'),
                   new SelectColumn('application_status', EncapsulationFunction: "''", Alias:'application_status'),
                   new SelectColumn('badge_type_id', JoinedTableAlias:'bs'),
                   new SelectColumn('payment_status', JoinedTableAlias:'bs'),
                   new SelectColumn('name', Alias:'badge_type_name', JoinedTableAlias:'typ')
                 )
            ),
            array(
                   new Join(
                       $this->g_badge_submission,
                       array(
                         'id' => 'application_id',
                       ),
                       alias:'bs'
                   ),

                   new Join(
                       $this->g_badge_type,
                       array(
                         'id' =>new SearchTerm('badge_type_id', null, JoinedTableAlias: 'bs'),
                       ),
                       alias:'typ'
                   ),
                  new Join(
                      $this->g_group,
                      array(
                        'id' => new SearchTerm('group_id', null, JoinedTableAlias: 'typ'),
                        new SearchTerm('event_id', $this->event_id)
                      ),
                      alias:'grp'
                  )
                 )
        );
    }
}
