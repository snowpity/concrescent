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
use CM3_Lib\util\badgeinfo;


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
        private eventinfo $eventinfo,
        private CurrentUserInfo $CurrentUserInfo,
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
        //Fetch the authenticated user's info
        $e_id = $this->CurrentUserInfo->GetEventId();
        $c_id = $this->CurrentUserInfo->GetContactId();
        $searchTerms = array(
          new SearchTerm('contact_id', $c_id),
          new SearchTerm('payment_status', 'Completed'),

        );

        $result = $this->badgeinfo->SearchBadges(null, $searchTerms, full: true);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
