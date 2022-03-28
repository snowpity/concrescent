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
        private a_addonpurchase $a_addonpurchase,
        private g_badge $g_badge,
        private s_badge $s_badge,
        private g_group $g_group,
        private g_badge_submission $g_badge_submission,
        private f_question $f_question,
        private f_response $f_response,
        private CurrentUserInfo $CurrentUserInfo,
        private FrontendUrlTranslator $FrontendUrlTranslator
    ) {
    }


    private $selectColumns = array(
      'id',
      'uuid',
      'display_id',
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
      //payment_txn_id
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

        switch ($data['context']) {
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
        return $this->addComputedColumns($result);
    }

    public function UpdateSpecificBadgeUnchecked($id, $context_code, $data, $allowedColumns = null)
    {
        $result = false;
        //Filter in the allowed columns to update
        if ($allowedColumns != null) {
            $data = array_intersect_key($data, array_flip($allowedColumns));
        }
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
                new SearchTerm('context', $context_code),
                new SearchTerm('context_id', $id),
            )
        );
        //Zip together the responses
        return array_combine(array_column($data, 'question_id'), array_column($data, 'response'));
    }

    public function SetSpecificBadgeResponses($id, $context_code, $responses)
    {
        //First fetch any that might exist already
        $existing =array_flip($this->f_response->Search(
            array('question_id'),
            array(
                new SearchTerm('context', $context_code),
                new SearchTerm('context_id', $id),
            )
        ));
        //Create/Update
        foreach ($responses as $question_id => $response) {
            $item = array(
                'context' => $context_code,
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
                'context' => $context_code,
                'context_id' => $id,
                'question_id' => $question_id
            );
            $this->f_response->Delete($item);
        }
    }

    public function GetAttendeeAddons($attendee_id)
    {
        return $this->a_addonpurchase->Search(
            array(
                'addon_id',
                'payment_txn_id',
                'payment_status'
            ),
            array(
                new SearchTerm('attendee_id', $attendee_id)
            )
        );
    }
    public function AddUpdateABadgeAddonUnchecked(&$data)
    {
        $current = $this->a_addonpurchase->Search(
            '*',
            array(
            new SearchTerm('attendee_id', $data['attendee_id']),
            new SearchTerm('addon_id', $data['addon_id']),
        )
        );
        if (count($current) > 0) {
            $this->a_addonpurchase->Update($data);
        } else {
            $this->a_addonpurchase->Create($data);
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

    public function SearchBadges($find, $order, $limit, $offset, &$totalRows)
    {
        $whereParts =
            empty($find) ? null :
             array(
            new SearchTerm('real_name', $find, Raw: 'MATCH(`real_name`, `fandom_name`, `notify_email`, `ice_name`, `ice_email_address`) AGAINST (? IN NATURAL LANGUAGE MODE) ')
        );
        // Invoke the Domain with inputs and retain the result
        $trA = 0;
        $trG = 0;
        $trS = 0;
        $a_data = $this->a_badge->Search($this->badgeView($this->a_badge_type, 'A'), $whereParts, $order, $limit, $offset, $trA);
        $s_data = $this->s_badge->Search($this->badgeView($this->s_badge_type, 'S'), $whereParts, $order, $limit, $offset, $trG);
        $g_data = $this->g_badge->Search($this->groupBadgeView(), $whereParts, $order, $limit, $offset, $trS);
        $totalRows =  $trA + $trG + $trS;
        return array_merge($a_data, $s_data, $g_data);
    }

    public function getASpecificBadge($id, $badge, $badgetype, $contextCode, $addView)
    {
        $view = $this->badgeView($badgetype, $contextCode);
        if ($addView instanceof View) {
            if (!is_null($addView->Columns)) {
                $view->Columns = array_merge($view->Columns, $addView->Columns);
            }
            if (!is_null($addView->Joins)) {
                $view->Joins = array_merge($view->Joins, $addView->Joins);
            }
        }
        return $badge->GetByIDorUUID($id, null, $view);
    }
    public function getASpecificGroupBadge($id, $addView)
    {
        $view = $this->groupBadgeView();
        if ($addView instanceof View) {
            if (!is_null($addView->Columns)) {
                $view->Columns = array_merge($view->Columns, $addView->Columns);
            }
            if (!is_null($addView->Joins)) {
                $view->Joins = array_merge($view->Joins, $addView->Joins);
            }
        }
        return $this->g_badge->GetByIDorUUID($id, null, $view);
    }
    public function getBadgetType($context_code, $badge_type_id)
    {
        $result = false;
        switch ($context_code) {
                case 'A':
                    $result =  $this->a_badge_type->GetByID($badge_type_id, $this->badgeTypeColumns());
                    break;
                case 'S':
                    $result =  $this->s_badge_type->GetByID($badge_type_id, $this->badgeTypeColumns());
                    break;
                default:
                    $result =  $this->g_badge_type->GetByID($badge_type_id, $this->badgeTypeColumns(true));
            }
        return $result;
    }


    public function searchASpecificBadge($uuid, $badge, $badgetype, $contextCode, $addView)
    {
        $view = $this->badgeView($badgetype, $contextCode);
        if ($addView instanceof View) {
            if (!is_null($addView->Columns)) {
                $view->Columns = array_merge($view->Columns, $addView->Columns);
            }
            if (!is_null($addView->Joins)) {
                $view->Joins = array_merge($view->Joins, $addView->Joins);
            }
        }
        return $badge->GetByIDorUUID(null, $uuid, $view);
    }
    public function searchASpecificGroupBadge($uuid, $addView)
    {
        $view = $this->groupBadgeView();
        if ($addView instanceof View) {
            if (!is_null($addView->Columns)) {
                $view->Columns = array_merge($view->Columns, $addView->Columns);
            }
            if (!is_null($addView->Joins)) {
                $view->Joins = array_merge($view->Joins, $addView->Joins);
            }
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
    public function badgeViewFullAddAttendee()
    {
        return new View(
            array(
                'notes',
                'uuid',
                'payment_txn_id'
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
                'payment_txn_id'
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
                   new SelectColumn('application_status', EncapsulationFunction: "''", Alias:'application_status'),
                   new SelectColumn('badge_type_id', JoinedTableAlias:'bs'),
                   new SelectColumn('payment_status', JoinedTableAlias:'bs'),
                   new SelectColumn('payment_txn_id', JoinedTableAlias:'bs'),
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

    public function addComputedColumns($result)
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
        //Add in the retrieve url
        $result['retrieve_url'] = $this->FrontendUrlTranslator->GetBadgeLoad($result);
        return $result;
    }
}
