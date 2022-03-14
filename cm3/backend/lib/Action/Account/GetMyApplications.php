<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\attendee\badge as a_badge;
use CM3_Lib\models\attendee\addonpurchase as a_addon;
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

class GetMyApplications
{
    private $selectColumns = array(
      'id',
      'badge_type_id',
      'display_id',
      'hidden',
      'uuid',
      'appplication_name1',
      'appplication_name2',
      'applicant_count',
      'application_status',
      'payment_status',
    );
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
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
        $c_id = $this->CurrentUserInfo->GetContactId();
        $e_id = $this->CurrentUserInfo->GetEventId();
        $searchTerms = array(
          new SearchTerm('contact_id', $c_id)
        );

        //First, attendee badges
        $result = $this->g_badge_submission->Search(
            new View(
                array_merge(
                    $this->selectColumns,
                    array(
                        new SelectColumn('context_code', JoinedTableAlias:'grp'),
                     )
                ),
                array(
                  new Join(
                      $this->g_badge_type,
                      array('id' => 'badge_type_id' ),
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



        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
