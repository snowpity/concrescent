<?php

namespace CM3_Lib\util;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\attendee\badgetype as a_badge_type;
use CM3_Lib\models\application\badgetype as g_badge_type;
use CM3_Lib\models\staff\badgetype as s_badge_type;
use CM3_Lib\models\attendee\badge as a_badge;
use CM3_Lib\models\attendee\addon as a_addon;
use CM3_Lib\models\attendee\addonmap as a_addonmap;
use CM3_Lib\models\attendee\addonpurchase as a_addonpurchase;
use CM3_Lib\models\application\submission as g_badge_submission;
use CM3_Lib\models\application\submissionapplicant as g_badge;
use CM3_Lib\models\application\group as g_group;
use CM3_Lib\models\staff\badge as s_badge;
use CM3_Lib\models\forms\question as f_question;
use CM3_Lib\models\forms\response as f_response;
use CM3_Lib\util\CurrentUserInfo;
use CM3_Lib\util\barcode;
use CM3_Lib\util\FrontendUrlTranslator;
use CM3_Lib\models\staff\department as s_department;
use CM3_Lib\models\staff\position as s_position;
use CM3_Lib\models\staff\assignedposition as s_assignedposition;

/**
 * Action.
 */
final class badgeinfo
{
    public function __construct(
        private a_badge_type $a_badge_type,
        private g_badge_type $g_badge_type,
        private s_badge_type $s_badge_type,
        private a_badge $a_badge,
        private a_addon $a_addon,
        private a_addonmap $a_addonmap,
        private a_addonpurchase $a_addonpurchase,
        private g_badge $g_badge,
        private s_badge $s_badge,
        private g_group $g_group,
        private g_badge_submission $g_badge_submission,
        private f_question $f_question,
        private f_response $f_response,
        private CurrentUserInfo $CurrentUserInfo,
        private FrontendUrlTranslator $FrontendUrlTranslator,
        private s_assignedposition $s_assignedposition,
        private s_department $s_department,
        private s_position $s_position,
    ) {
    }


    private $selectColumns = array(
      'id',
      'uuid',
      'display_id',
      'contact_id',
      'real_name',
      'fandom_name',
      'name_on_badge',
      'date_of_birth',
      'notify_email',
      'time_printed',
      'time_checked_in'
      //View will add:
      //context_code
      //application_status
      //badge_type_id
      //payment_status
      //badge_type_name

      //Full view will add:
      //payment_id
      //notes
      //large_name
      //small_name
      //application_name
      //assigned_slot_name (work in progress name)
      //assigned_department
      //assigned_position
      //assigned_department_and_position_concat
      //assigned_department_and_position_hyphen
      //badge_id_display
      //uuid
      //qr_data

    );

    public function CreateSpecificBadgeUnchecked($data, $allowedColumns = null)
    {
        $result = false;
        //Filter in the allowed columns to update
        if ($allowedColumns != null) {
            $data = array_intersect_key($data, array_flip($allowedColumns));
        }
        //Actually don't accept updates to ID and uuid
        unset($data['id']);
        unset($data['uuid']);

        switch ($data['context_code']) {
            case 'A':
                $result =  $this->a_badge->Create($data);
                break;
            case 'S':
                $result =  $this->s_badge->Create($data);
                break;
            default:
                $result =  $this->g_badge->Create($data);
        }

        return $result;
    }

    public function GetSpecificBadge($id, $context_code, $full = false)
    {
        $result = false;
        switch ($context_code) {
            case 'A':
                $result =  $this->getASpecificBadge($id, $this->a_badge, $this->a_badge_type, 'A', $full ? $this->badgeViewFullAddAttendee() : null);
                if ($result !== false) {
                    $result['addons'] = [];
                    $a_addons = $this->a_addonpurchase->Search(array(
                    'addon_id',
                    'payment_status'
                ), array(
                    new SearchTerm('attendee_id', $id)
                ));
                    foreach ($a_addons as $addon) {
                        $result['addons'][] = array(
                        'addon_id' => $addon['addon_id'],
                        'addon_payment_status' => $addon['payment_status']
                    );
                    }
                }

                break;
            case 'S':
                $result =  $this->getASpecificBadge($id, $this->s_badge, $this->s_badge_type, 'S', $full ? $this->badgeViewFullAddStaff() : null);
                break;
            default:
                $result =  $this->getASpecificGroupBadge($id, $full ? $this->badgeViewFullAddGroup() : null);
        }

        if ($result === false || !$full) {
            return $result;
        }
        //Add in form responses
        $result['form_responses'] = $this->GetSpecificBadgeResponses($id, $context_code);
        //Add in supplementary
        $this->addSupplementaryBadgeData($result);
        return $this->addComputedColumns($result, true);
    }

    public function UpdateSpecificBadgeUnchecked($id, $context_code, $data, $allowedColumns = null)
    {
        $result = false;
        //Filter in the allowed columns to update
        if ($allowedColumns != null) {
            $data = array_intersect_key($data, array_flip($allowedColumns));
        }
        //Actually don't accept updates to uuid
        unset($data['uuid']);
        //Slide in the ID
        $data['id'] = $id;
        switch ($context_code) {
            case 'A':
                $result =  $this->a_badge->Update($data);
                break;
            case 'S':
                $result =  $this->s_badge->Update($data);
                break;
            default:
                $result =  $this->g_badge->Update($data);
        }
        return $result;
    }

    public function setNextDisplayIDSpecificBadge($id, $context_code)
    {
        $result = false;
        //Slide in the ID
        $data = array('id' => $id);
        switch ($context_code) {
            case 'A':
                $next = $this->a_badge->Search(
                    new View(
                        array(
                       'display_id',
                    ),
                        array(
                       new Join(
                           $this->a_badge_type,
                           array(
                             'id' => 'badge_type_id',
                             new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId())
                           ),
                           alias:'typ'
                       )
                     )
                    ),
                    array(
                        new SearchTerm('display_id', '', 'IS NOT')
                    ),
                    array('display_id'=>true),
                    1
                );
                $data['display_id'] = (count($next) > 0) ? $next[0]['display_id'] + 1 : 1;
                $result =  $this->a_badge->Update($data);
                break;
            case 'S':
                $next = $this->s_badge->Search(
                    new View(
                        array(
                       'display_id',
                    ),
                        array(
                       new Join(
                           $this->s_badge_type,
                           array(
                             'id' => 'badge_type_id',
                             new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId())
                           ),
                           alias:'typ'
                       )
                     )
                    ),
                    array(
                        new SearchTerm('display_id', '', 'IS NOT')
                    ),
                    array('display_id'=>true),
                    1
                );
                $data['display_id'] = (count($next) > 0) ? $next[0]['display_id'] + 1 : 1;
                $result =  $this->s_badge->Update($data);
                break;
            default:
            //TODO: Fix
                $next = $this->g_badge->Search(
                    new View(
                        array(
                            'display_id'
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
                                new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId()),
                                new SearchTerm('context_code', $context_code)
                              ),
                              alias:'grp'
                          )
                         )
                    ),
                    array(
                        new SearchTerm('display_id', '', 'IS NOT')
                    ),
                    array('display_id'=>true),
                    1
                );


                $result =  $this->g_badge->Update($data);
        }
        return $result;
    }

    public function GetSpecificBadgeResponses($id, $context_code)
    {
        $data = $this->f_response->Search(
            array('question_id','response'),
            array(
                new SearchTerm('context_code', $context_code),
                new SearchTerm('context_id', $id),
            )
        );
        //Zip together the responses
        return array_combine(array_column($data, 'question_id'), array_column($data, 'response'));
    }

    public function SetSpecificBadgeResponses($id, $context_code, $responses)
    {
        //First fetch any that might exist already
        $existing =array_flip(array_column($this->f_response->Search(
            array('question_id'),
            array(
                new SearchTerm('context_code', $context_code),
                new SearchTerm('context_id', $id),
            )
        ), 'question_id'));
        //Create/Update
        foreach ($responses as $question_id => $response) {
            $item = array(
                'context_code' => $context_code,
                'context_id' => $id,
                'question_id' => $question_id,
                'response' => $response
            );
            if (isset($existing[$question_id])) {
                $this->f_response->Update($item);
            } else {
                $this->f_response->Create($item);
            }
        }
        //Delete the missing ones
        foreach (array_diff($existing, $responses) as $question_id => $response) {
            $item = array(
                'context_code' => $context_code,
                'context_id' => $id,
                'question_id' => $question_id
            );
            $this->f_response->Delete($item);
        }
    }

    public function GetAttendeeAddonsAvailable($badge_type_id)
    {
        return $this->a_addon->Search(
            new View(
                array(
                'id',
                'active',
                'name',
                'description',
                'price',
                'payable_onsite',
            ),
                array(

               new Join(
                   $this->a_addonmap,
                   array(
                     'addon_id' => 'id',
                     new SearchTerm('badge_type_id', $badge_type_id)
                   ),
                   alias:'map'
               )
            )
            ),
            array(
                $this->CurrentUserInfo->EventIdSearchTerm()
            )
        );
    }
    public function GetAttendeeAddons($attendee_id)
    {
        return $this->a_addonpurchase->Search(
            array(
                'addon_id',
                'payment_id',
                'payment_status'
            ),
            array(
                new SearchTerm('attendee_id', $attendee_id)
            )
        );
    }
    public function AddUpdateABadgeAddonUnchecked(&$data)
    {
        $current = $this->a_addonpurchase->Exists(
            array(
                'attendee_id'=> $data['attendee_id'] ?? 0,
                'addon_id'=> $data['addon_id'] ?? 0,
            )
        );
        if ($current > 0) {
            return $this->a_addonpurchase->Update($data);
        } else {
            return $this->a_addonpurchase->Create($data);
        }
    }

    public function SearchSpecificBadge($uuid, $context_code, $full)
    {
        $result = false;
        switch ($context_code) {
            case 'A':
                $result =  $this->searchASpecificBadge($uuid, $this->a_badge, $this->a_badge_type, 'A', $full ? $this->badgeViewFullAddAttendee() : null);

                break;
            case 'S':
                $result =  $this->searchASpecificBadge($uuid, $this->s_badge, $this->s_badge_type, 'S', $full ? $this->badgeViewFullAddStaff() : null);
                break;
            default:
                $result =  $this->searchASpecificGroupBadge($uuid, $full ? $this->badgeViewFullAddGroup() : null);
        }
        if ($result === false || !$full) {
            return $result;
        }
        return $this->addComputedColumns($result);
    }

    public function SearchBadgesText($context, string $searchText, $order, $limit, $offset, &$totalRows)
    {
        $whereParts =
        empty($searchText) ? null :
        array(
            new SearchTerm('real_name', $searchText, Raw: 'MATCH(`real_name`, `fandom_name`, `notify_email`, `ice_name`, `ice_email_address`) AGAINST (? IN NATURAL LANGUAGE MODE) ')
        );
        $wherePartsSimpler = array(
            new SearchTerm(
                '',
                '',
                subSearch: array(
                    new SearchTerm('real_name', '%' . $searchText . '%', 'LIKE', 'OR'),
                    new SearchTerm('fandom_name', '%' . $searchText . '%', 'LIKE', 'OR'),
                    new SearchTerm('notify_email', '%' . $searchText . '%', 'LIKE', 'OR'),
                    new SearchTerm('ice_name', '%' . $searchText . '%', 'LIKE', 'OR'),
                    new SearchTerm('ice_email_address', '%' . $searchText . '%', 'LIKE', 'OR'),
                )
            ));
        $result = $this->SearchBadges($context, $whereParts, $order, $limit, $offset, $totalRows);
        //If we got nothing, switch to a simpler search
        if (count($result) == 0) {
            $result =  $this->SearchBadges($context, $wherePartsSimpler, $order, $limit, $offset, $totalRows);
        }
        return $result;
    }

    public function SearchBadges($context, $terms, ?array $order = null, int $limit = -1, int $offset = 0, &$totalRows = null, $full = false)
    {
        // Invoke the Domain with inputs and retain the result
        $trA = 0;
        $trG = 0;
        $trS = 0;
        $a_bv = $this->badgeView($this->a_badge_type, 'A');
        $s_bv = $this->staffBadgeView($this->s_badge_type, 'S');
        $g_bv = $this->groupBadgeView();
        if ($full) {
            $this->MergeView($a_bv, $this->badgeViewFullAddAttendee());
            $this->MergeView($s_bv, $this->badgeViewFullAddStaff());
            $this->MergeView($g_bv, $this->badgeViewFullAddGroup());
        }
        $a_terms = $this->AdjustSearchTerms($terms, $a_bv);
        $s_terms = $this->AdjustSearchTerms($terms, $s_bv);
        $g_terms = $this->AdjustSearchTerms($terms, $g_bv);
        //Add to the group search if context specified
        $g_terms[] = new SearchTerm('context_code', $context, is_null($context) ? 'IS' : '=', JoinedTableAlias:'grp');

        $a_data = (($context ?? 'A') == 'A') ? $this->a_badge->Search($a_bv, $a_terms, $order, $limit, $offset, $trA) : array();
        $s_data = (($context ?? 'S') == 'S') ? $this->s_badge->Search($s_bv, $s_terms, $order, $limit, $offset, $trG) : array();
        //$this->g_badge->debugThrowBeforeSelect = true;
        $g_data = $this->g_badge->Search($g_bv, $g_terms, $order, $limit, $offset, $trS);
        $totalRows =  $trA + $trG + $trS;


        //Add in any addons
        $attendeeIds = array_column($a_data, 'id');
        if (count($attendeeIds) == 0) {
            $attendeeIds[] = 0;
        }
        $a_addons = $this->a_addonpurchase->Search(array(
            'attendee_id',
            'addon_id'
        ), array(
            new SearchTerm('attendee_id', $attendeeIds, 'IN'),
            new SearchTerm('payment_status', 'Completed')
        ));

        $result = array_merge($a_data, $s_data, $g_data);

        //Loop the badges to add their addons, if any addons were returned for it
        foreach ($result as &$badge) {
            $badge['addons'] = array();
            if ($badge['context_code'] == 'A') {
                foreach ($a_addons as $addon) {
                    if ($addon['attendee_id'] == $badge['id']) {
                        $badge['addons'][] = array(
                        'addon_id' => $addon['addon_id']
                    );
                    }
                }
            }

            //While we're here, add the computed columns too
            $badge = $this->addComputedColumns($badge, false);
        }

        //Fetch associated form responses if full == true
        if ($full) {
            //Generate searchTerms for each of the found badges
            $f_searchTerms = [];
            foreach ($result as &$badge) {
                $f_searchTerms[] = new SearchTerm(
                    '',
                    '',
                    TermType: 'OR',
                    subSearch: [
                            new SearchTerm('context_code', $badge['context_code']),
                            new SearchTerm('context_id', $badge['id']),
                    ]
                );
            }
            $f_responsedata = $this->f_response->Search(
                array('context_id','context_code','question_id','response'),
                $f_searchTerms
            );

            foreach ($result as &$badge) {
                $f_filteredbadgedata = array_filter($f_responsedata, function ($f_response) use ($badge) {
                    return $f_response['context_id'] == $badge['id']
                    &&  $f_response['context_code'] == $badge['context_code'] ;
                });
                //Zip together the responses
                $badge['form_responses'] = array_combine(array_column($f_filteredbadgedata, 'question_id'), array_column($f_filteredbadgedata, 'response'));
            }
        }

        return $result;
    }

    private function AdjustSearchTerms($terms, $badgeView)
    {
        if (is_null($terms) || is_null($badgeView)) {
            return $terms;
        }
        $result = array();
        foreach ($terms as $sterm) {
            //Search the view
            $term = clone $sterm;
            foreach ($badgeView->Columns as $selCol) {
                if ($selCol instanceof SelectColumn) {
                    //Does this match a column with an alias?
                    if (($selCol->ColumnName == $term->ColumnName || $selCol->Alias == $term->ColumnName)
                    && $selCol->JoinedTableAlias != null) {
                        $term->JoinedTableAlias = $selCol->JoinedTableAlias;
                        break;
                    }
                }
            }
            $result[] = $term;
        }
        return $result;
    }

    private function MergeView(View &$view, ?View $addView)
    {
        if (is_null($addView)) {
            return;
        }
        if (!is_null($addView->Columns)) {
            $view->Columns = array_merge($view->Columns, $addView->Columns);
        }
        if (!is_null($addView->Joins)) {
            $view->Joins = array_merge($view->Joins, $addView->Joins);
        }
    }

    public function getASpecificBadge($id, $badge, $badgetype, $contextCode, $addView)
    {
        $view = $contextCode == 'A' ? $this->badgeView($badgetype, $contextCode)
        : $this->staffBadgeView($badgetype, $contextCode);
        if ($addView instanceof View) {
            $this->MergeView($view, $addView);
        }
        return $badge->GetByIDorUUID($id, null, $view);
    }
    public function getASpecificGroupBadge($id, $addView)
    {
        $view = $this->groupBadgeView();
        if ($addView instanceof View) {
            $this->MergeView($view, $addView);
        }
        return $this->g_badge->GetByIDorUUID($id, null, $view);
    }
    public function checkBadgeTypeBelongsToEvent($context_code, $badge_type_id)
    {
        $result = false;
        switch ($context_code) {
                    case 'A':
                        $found = $this->a_badge_type->Search(
                            array('id'),
                            array(
                            new SearchTerm('id', $badge_type_id),
                            new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId())
                        )
                        );
                        $result = count($found) > 0;
                        break;
                    case 'S':
                        $found = $this->s_badge_type->Search(
                            array('id'),
                            array(
                            new SearchTerm('id', $badge_type_id),
                            new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId())
                        )
                        );
                        $result = count($found) > 0;
                        break;
                    default:
                        $result =  $this->g_badge_type->Search(new View(
                            array('id'),
                            array(
                                  new Join(
                                      $this->g_group,
                                      array(
                                        'group_id' => 'group_id',
                                        new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId()),
                                        new SearchTerm('context_code', $context_code)
                                      ),
                                      alias:'grp'
                                  )
                                 )
                        ), array(
                                 new SearchTerm('id', $badge_type_id),
                             ));
                             $result = count($found) > 0;
                }
        return $result;
    }
    public function getBadgetType($context_code, $badge_type_id)
    {
        $result = false;
        switch ($context_code) {
                case 'A':
                    $result =  $this->a_badge_type->GetByID($badge_type_id, $this->badgeTypeColumns());
                    break;
                case 'S':
                    $result =  $this->s_badge_type->GetByID($badge_type_id, $this->badgeTypeColumns(true));
                    break;
                default:
                    $result =  $this->g_badge_type->GetByID($badge_type_id, $this->badgeTypeColumns(true));
            }
        return $result;
    }


    public function searchASpecificBadge($uuid, $badge, $badgetype, $contextCode, $addView)
    {
        $view = $contextCode == 'A' ? $this->badgeView($badgetype, $contextCode)
        : $this->staffBadgeView($badgetype, $contextCode);
        if ($addView instanceof View) {
            $this->MergeView($view, $addView);
        }
        return $badge->GetByIDorUUID(null, $uuid, $view);
    }
    public function searchASpecificGroupBadge($uuid, $addView)
    {
        $view = $this->groupBadgeView();
        if ($addView instanceof View) {
            $this->MergeView($view, $addView);
        }
        return $this->g_badge->GetByIDorUUID(null, $uuid, $view);
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
               'payment_promo_price',
               'payment_badge_price',
               new SelectColumn('name', Alias:'badge_type_name', JoinedTableAlias:'typ'),
               new SelectColumn('payable_onsite', Alias:'badge_type_payable_onsite', JoinedTableAlias:'typ'),
             )
            ),
            array(

               new Join(
                   $badgetype,
                   array(
                     'id' => 'badge_type_id',
                     new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId())
                   ),
                   alias:'typ'
               )
             )
        );
    }

    public function staffBadgeView($badgetype, $contextCode)
    {
        return new View(
            array_merge(
                $this->selectColumns,
                array(
               new SelectColumn('context_code', EncapsulationFunction: "'".$contextCode."'", Alias:'context_code'),
               'application_status',
               'badge_type_id',
               'payment_status',
               'payment_promo_price',
               'payment_badge_price',
               new SelectColumn('name', Alias:'badge_type_name', JoinedTableAlias:'typ'),
               new SelectColumn('payable_onsite', Alias:'badge_type_payable_onsite', JoinedTableAlias:'typ'),
             )
            ),
            array(

               new Join(
                   $badgetype,
                   array(
                     'id' => 'badge_type_id',
                     new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId())
                   ),
                   alias:'typ'
               )
             )
        );
    }
    public function badgeViewFullAddAttendee()
    {
        return new View(
            array(
                'notes',
                'uuid',
                'payment_id'
            ),
            array(

            ),
        );
    }
    public function badgeViewFullAddStaff()
    {
        return new View(
            array(
                'notes',
                'uuid',
                'payment_id'
            )
        );
    }

    public function groupBadgeView()
    {
        return new View(
            array_merge(
                $this->selectColumns,
                array(
                   new SelectColumn('context_code', JoinedTableAlias:'grp'),
                   new SelectColumn('application_status', JoinedTableAlias:'bs'),
                   new SelectColumn('badge_type_id', JoinedTableAlias:'bs'),
                   new SelectColumn('payment_status', JoinedTableAlias:'bs'),
                   new SelectColumn('payment_id', JoinedTableAlias:'bs'),
                   new SelectColumn('name', Alias:'badge_type_name', JoinedTableAlias:'typ'),
                   new SelectColumn('payable_onsite', Alias:'badge_type_payable_onsite', JoinedTableAlias:'typ'),
                   new SelectColumn('payment_deferred', Alias:'badge_type_payment_deferred', JoinedTableAlias:'typ')
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
                        new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId())
                      ),
                      alias:'grp'
                  )
                 )
        );
    }
    public function badgeViewFullAddGroup()
    {
        return new View(
            array(
                'notes',
                'uuid'
            ),
            array()
        );
    }

    public function badgeTypeColumns($include_defferred_pay = false)
    {
        return new View(
            array(
                'active',
                'name',
                'description',
                'price',
                'payable_onsite',
                $include_defferred_pay ? 'payment_deferred' : null
            ),
            array()
        );
    }
    public function addSupplementaryBadgeData(&$result)
    {
        switch ($result['context_code']) {
                    case 'A':
                        //$result =  $this->a_badge->Create($data);
                        break;
                    case 'S':
                        $result['assigned_positions'] = $this->s_assignedposition->Search(
                            new View(
                                array(
                                    'position_id','onboard_completed','onboard_meta','date_created','date_modified',
                                    new SelectColumn('is_exec', JoinedTableAlias:'p'),
                                    new SelectColumn('name', Alias:'position_text', JoinedTableAlias:'p'),
                                    new SelectColumn('department_id', JoinedTableAlias:'p'),
                                    new SelectColumn('name', Alias:'department_text', JoinedTableAlias:'d'),
                                ),
                                array(
                                    new Join(
                                        $this->s_position,
                                        array('id'=>'position_id'),
                                        alias:'p'
                                    ),
                                    new Join(
                                        $this->s_department,
                                        array('id'=>new SearchTerm('department_id', null, JoinedTableAlias:'p')),
                                        alias:'d'
                                    ),
                                )
                            ),
                            array(
                                new SearchTerm('staff_id', $result['id'])
                            )
                        );
                        //$result =  $this->s_badge->Create($data);
                        break;
                    default:
                        $result =  $this->g_badge->Create($data);
                }
    }

    public function addComputedColumns($result, $includeImageData = false)
    {
        $result['display_name'] = $result['real_name'];
        //Add some computed helper columns
        switch ($result['name_on_badge']) {
            case 'Fandom Name Large, Real Name Small':
                $result['only_name'] = '';
                $result['large_name'] = $result['fandom_name'];
                $result['small_name'] = $result['real_name'];
                $result['display_name'] = trim($result['fandom_name']) .' (' . $result['real_name'] . ')';
                break;
            case 'Real Name Large, Fandom Name Small':
                $result['only_name'] = '';
                $result['large_name'] = $result['real_name'];
                $result['small_name'] = $result['fandom_name'];
                $result['display_name'] = trim($result['real_name']) .' (' . $result['fandom_name'] . ')';
                break;
            case 'Fandom Name Only':
                $result['only_name'] = $result['fandom_name'];
                break;
            case 'Real Name Only':
                $result['only_name'] = $result['real_name'];
                break;
        }
        $result['badge_id_display'] = $result['context_code'] . $result['display_id'];
        $result['qr_data'] = 'CM*' . $result['context_code'] . $result['display_id'] . '*' . $result['uuid'];
        //Add in the retrieve url
        $result['retrieve_url'] = $this->FrontendUrlTranslator->GetBadgeLoad($result);
        //Add in the cart url
        $result['cart_url'] = $this->FrontendUrlTranslator->GetCartLoad($result);

        if ($includeImageData) {
            $bc = new barcode_generator();

            $image = $bc->render_image('qr', $result['qr_data'], array(
                'w'=>150,
                'h'=>150
            ));
            //Open a stream to buffer the output
            $stream = fopen("php://memory", "w+");
            //write it out with max compression
            imagepng($image, $stream, 9);
            //Release resource for the image since we're done with it
            imagedestroy($image);
            //Set the cursor to the beginning
            rewind($stream);
            //Stuff it into a string
            $png = stream_get_contents($stream);
            $result['qr_data_uri'] = 'data:image/png;base64,' . base64_encode($png);
        }
        return $result;
    }

    //Utility
    public function parseQueryParamsPagination(array $qp, $defaultSortColumn = 'id', $defaultSortDesc = false)
    {
        //Interpret order parameters
        $sortBy = explode(',', $qp['sortBy'] ??'');
        $idAdded = false;
        //Add the ID
        if (empty($sortBy[0])) {
            $idAdded = true;
            $sortBy[0] = $defaultSortColumn;
        } else {
            //$idAdded = true;
            $sortBy[] = $defaultSortColumn;
        }
        $sortDesc = array_map(function ($v) {
            return $v == 'true' ? 1 : 0;
        }, explode(',', $qp['sortDesc']??''));
        //Ensure the ID sort is descending
        if ($idAdded) {
            $sortDesc[count($sortDesc) - 1] = $defaultSortDesc;
        } else {
            //If we actually had the ID specified, un-add the ID column we forced
            if (array_count_values($sortBy)[$defaultSortColumn] > 1) {
                array_pop($sortBy);
                array_pop($sortDesc);
            }
        }

        $order =array_combine(
            $sortBy,
            $sortDesc
        );
        //die(print_r($order, true));

        $page      = ($qp['page']?? 0 > 0) ? $qp['page'] : 1;
        $limit     = $qp['itemsPerPage']?? -1; // Number of posts on one page
        $offset      = ($page - 1) * $limit;
        if ($offset < 0) {
            $offset = 0;
        }
        return array(
            'order'=>$order,
            'limit'=>$limit,
            'offset'=>$offset,
        );
    }
}
