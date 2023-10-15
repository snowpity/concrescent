<?php

namespace CM3_Lib\Action\AdminUser;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\Join;
use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\View;

use CM3_Lib\models\admin\user;
use CM3_Lib\models\contact;
use CM3_Lib\util\badgeinfo;
use CM3_Lib\util\TokenGenerator;
use CM3_Lib\util\CurrentUserInfo;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        private user $user,
        private contact $contact,
        private badgeinfo $badgeinfo,
        private TokenGenerator $tokenGenerator,
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {

        $whereParts = array(
          new SearchTerm('username', '%' . ($request->getQueryParams()['find']??'') .'%', 'LIKE'),
        );

        $qp = $request->getQueryParams();

        $pg = $this->badgeinfo->parseQueryParamsPagination($qp, 'contact_id', false);
        $totalRows = 0;

        // Invoke the Domain with inputs and retain the result
        $data = $this->user->Search(new View([
                    'contact_id','username','active','permissions',
                    new SelectColumn('real_name', Alias:'real_name', EncapsulationFunction: 'IFNULL(?,\'Anonymous\')', JoinedTableAlias:'c'),
                    new SelectColumn('email_address', Alias:'email_address', EncapsulationFunction: 'IFNULL(?,\'\')', JoinedTableAlias:'c')
                ], [

                   new Join(
                       $this->contact,
                       array(
                         'id' => new SearchTerm('contact_id', null)
                     ),
                       'LEFT',
                       alias:'c'
                   )
               ]), $whereParts, $pg['order'], $pg['limit'], $pg['offset'], $totalRows);
        
        //Sput in permissions details
        $data = array_map(function($userdata){
            $uperms = $this->tokenGenerator->decodePermissionsString($userdata['permissions']);
            $userdata['HasPermsForEvent'] = array_key_exists(strval($this->CurrentUserInfo->GetEventId()),$uperms->EventPerms);
            $userdata['EventsWithPerms'] = array_keys($uperms->EventPerms);
            $userdata['IsGlobalAdmin'] = $uperms->IsGlobalAdmin();
            unset($userdata['permissions']);
            // $userdata['permissions'] = json_encode($uperms);
            return $userdata;
        },$data);

        $response = $response->withHeader('X-Total-Rows', (string)$totalRows);
        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
