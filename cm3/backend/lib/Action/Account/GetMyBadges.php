<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\attendee\badge as a_badge;
use CM3_Lib\models\attendee\badgetype as a_badge_type;
use CM3_Lib\models\attendee\addonpurchase as a_addonpurchase;
use CM3_Lib\models\application\submissionapplicant as g_badge;
use CM3_Lib\models\application\submission as g_badge_submission;
use CM3_Lib\models\application\badgetype as g_badge_type;
use CM3_Lib\models\application\group as g_group;
use CM3_Lib\models\eventinfo;
use CM3_Lib\util\CurrentUserInfo;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetMyBadges
{
    private $selectColumns = array(
      'id',
      'display_id',
      'hidden',
      'can_transfer',
      'uuid',
      'real_name',
      'fandom_name',
      'name_on_badge',
      'date_of_birth',
      'notify_email',
      'can_transfer',
      'ice_name',
      'ice_relationship',
      'ice_email_address',
      'ice_phone_number',
    );
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private a_badge $a_badge,
        private a_badge_type $a_badge_type,
        private a_addonpurchase $a_addonpurchase,
        private g_badge $g_badge,
        private g_badge_submission $g_badge_submission,
        private g_badge_type $g_badge_type,
        private g_group $g_group,
        private eventinfo $eventinfo,
        private CurrentUserInfo $CurrentUserInfo
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
        //Fetch the authenticated user's info
        $e_id = $this->CurrentUserInfo->GetEventId();
        $c_id = $this->CurrentUserInfo->GetContactId();
        $searchTerms = array(
          new SearchTerm('contact_id', $c_id)
        );

        //First, attendee badges
        $a_badges = $this->a_badge->Search(
            new View(
                array_merge(
                    $this->selectColumns,
                    array(
                    new SelectColumn('context_code', EncapsulationFunction: "'A'", Alias:'context_code'),
                    'badge_type_id',
                    new SelectColumn('name', Alias: 'badge_type_name', JoinedTableAlias:'typ'),
                    'payment_status'
                  )
                ),
                array(

                new Join(
                    $this->a_badge_type,
                    array(
                      'id' => 'badge_type_id',
                      new SearchTerm('event_id', $e_id)
                    ),
                    alias:'typ'
                )
              )
            ),
            $searchTerms
        );

        //Add in any addons
        $attendeeIds = array_column($a_badges, 'id');
        if (count($attendeeIds) < 1) {
            $attendeeIds = array(0);
        }
        $a_addons = $this->a_addonpurchase->Search(array(
            'attendee_id',
            'addon_id'
        ), array(
            new SearchTerm('attendee_id', $attendeeIds, 'IN'),
            new SearchTerm('payment_status', 'Completed')
        ));


        //Loop the badges to add their addons, if any addons were returned for it
        foreach ($a_badges as &$badge) {
            $badge['addons'] = array();
            foreach ($a_addons as $addon) {
                if ($addon['attendee_id'] == $badge['id']) {
                    $badge['addons'][] = array(
                        'addon_id' => $addon['addon_id']
                    );
                }
            }
        }

        //And group application badges
        $g_badges = $this->g_badge->Search(
            new View(
                array_merge(
                    $this->selectColumns,
                    array(
                new SelectColumn('context_code', JoinedTableAlias:'grp'),
                new SelectColumn('badge_type_id', JoinedTableAlias:'sub'),
                new SelectColumn('name', Alias: 'badge_type_name', JoinedTableAlias:'typ'),
                new SelectColumn('payment_status', JoinedTableAlias:'sub')
             )
                ),
             //And the join so we can get the badge_type_id
             array(
               new Join(
                   $this->g_badge_submission,
                   array('id' => 'application_id'),
                   alias:'sub'
               ),
                 new Join(
                     $this->g_badge_type,
                     array('id' => new SearchTerm('badge_type_id', null, JoinedTableAlias:'sub') ),
                     alias:'typ'
                 ),
               new Join(
                   $this->g_group,
                   array(
                     'id' =>  new SearchTerm('group_id', null, JoinedTableAlias:'typ'),
                     new SearchTerm('event_id', $e_id)
                   ),
                   alias:'grp'
               )
             )
            ),
            $searchTerms
        );

        $result = array_merge($a_badges, $g_badges);

        //Munge in some extras
        array_walk($result, function (&$badge) {
            $badge['qr_data'] = 'CM*' . $badge['context_code'] . $badge['display_id'] . '*' . $badge['uuid'];
        });

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
