<?php

namespace CM3_Lib\util;

use Respect\Validation\Validator as v;
use CM3_Lib\database\TableValidator;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\SelectColumn;

use CM3_Lib\models\attendee\badgetype as a_badge_type;
use CM3_Lib\models\application\badgetype as g_badge_type;
use CM3_Lib\models\staff\badgetype as s_badge_type;
use CM3_Lib\models\attendee\badge as a_badge;
use CM3_Lib\models\application\submission as g_badge_submission;
use CM3_Lib\models\application\submissionapplicant as g_badge;
use CM3_Lib\models\application\group as g_group;
use CM3_Lib\models\staff\badge as s_badge;
use CM3_Lib\models\attendee\addon as a_addon;
use CM3_Lib\models\attendee\addonmap as a_addonmap;
use CM3_Lib\models\application\addon as g_addon;
use CM3_Lib\models\application\addonmap as g_addonmap;

use CM3_Lib\util\CurrentUserInfo;

final class badgevalidator
{
    public function __construct(
        private a_badge_type $a_badge_type,
        private g_badge_type $g_badge_type,
        private s_badge_type $s_badge_type,
        private a_badge $a_badge,
        private g_badge $g_badge,
        private s_badge $s_badge,
        private g_group $g_group,
        private a_addon $a_addon,
        private a_addonmap $a_addonmap,
        private g_addon $g_addon,
        private g_addonmap $g_addonmap,
        private g_badge_submission $g_badge_submission,
        private badgepromoapplicator $badgepromoapplicator,
        private CurrentUserInfo $CurrentUserInfo
    ) {
    }

    //Returns an array of the errors in this badge
    public function ValdateCartBadge(&$item)
    {
        $result = array();
        switch ($item['context_code'] ?? 'A') {
            case 'A':
                //Init the validator
                $v = new TableValidator($this->a_badge);
                //Add the badge-type validations
                if (!empty($item['badge_type_id'])) {
                    $badgeType = $this->a_badge_type->GetByID($item['badge_type_id'], $this->getBadgeTypeView($this->a_badge));
                    if ($badgeType !== false) {
                        $this->AddBadgeValidations($v, $badgeType, $item);
                    } else {
                        //Add only the validator for badge_type_id
                        $v->addColumnValidator('badge_type_id', v::In($this->GetValidTypeIdsForContext('A')), true);
                    }
                }


                //Special: Also apply the current promo if specified
                if (!empty($item['payment_promo_code'])) {
                    $this->badgepromoapplicator->TryApplyCode($item, $item['payment_promo_code']);
                }
                break;
            case 'S':
                //Init the validator
                $v = new TableValidator($this->s_badge);
                //Add the badge-type validations
                if (!empty($item['badge_type_id'])) {
                    $badgeType = $this->s_badge_type->GetByID($item['badge_type_id'], $this->getBadgeTypeView($this->s_badge));
                    if ($badgeType !== false) {
                        $this->AddBadgeValidations($v, $badgeType, $item);
                    } else {
                        //Add only the validator for badge_type_id
                        $v->addColumnValidator('badge_type_id', v::In($this->GetValidTypeIdsForContext('S')), true);
                    }
                }
                break;
            default: //Assume it's an application
                //Init the validator
                $v = new TableValidator($this->g_badge_submission);
                //Add the badge-type validations
                if (!empty($item['badge_type_id'])) {
                    $badgeType = $this->g_badge_type->GetByID($item['badge_type_id'], $this->getBadgeTypeView($this->g_badge_submission));
                    if ($badgeType !== false) {
                        $this->AddBadgeValidations($v, $badgeType, $item, true);
                    } else {
                        //Add only the validator for badge_type_id
                        $v->addColumnValidator('badge_type_id', v::In($this->GetValidTypeIdsForContext($item['context_code'])), true);
                    }
                }
                //TODO: Add submission applicants
        }

        //Add the questions validator
        //TODO: Implement

        $v->Validate($item);
        return $v->GetErrors();
    }

    private function getBadgeTypeView($badges, $groupApp = false)
    {
        return new View(array(
            'start_date',
            'end_date',
            'min_age',
            'max_age',
            'price',
            new SelectColumn('quantity_sold', EncapsulationFunction: 'quantity - ifnull(?,0)', Alias: 'quantity_remaining', JoinedTableAlias: 'q')
        ), array(
        new Join(
            $badges,
            array(
              'badge_type_id'=>'id',
            ),
            'LEFT',
            'q',
            array(
              new SelectColumn('badge_type_id', true),
              new SelectColumn('id', false, 'count(?)', 'quantity_sold')
          ),
            array(
             new SearchTerm('payment_status', 'Completed'),
           )
        )));
    }

    private function AddBadgeValidations(TableValidator &$v, array $badgetypeData, &$item, $groupApp = false)
    {
        //TODO: Test for things like badge upgrades for the start_date and end_date?

        //TODO: This isn't right, need to advance the year in accordance to the event date and today....
        $bday = new v();
        if (!empty($badgetypeData['min_age'])) {
            $bday = $bday->MinAge($badgetypeData['min_age']);
        }
        if (!empty($badgetypeData['max_age'])) {
            $bday = $bday->MaxAge($badgetypeData['max_age']);
        }
        if (!$groupApp) {
            $v->addColumnValidator('date_of_birth', $bday);
        } else {
            //Add the validator to the applicant badges instead
        }

        // //Check for quantity-limited badges
        // $available_type_ids = array_column(array_filter(
        //     $badgetypeData,
        //     function ($i) {
        //
        //         return $i['quantity_remaining'] !== 0;
        //     }
        // ), 'id');
        if ($badgetypeData['quantity_remaining'] === 0) {
            $v->addColumnValidator('badge_type_id', v::alwaysInvalid(), true);
        }


        $v->addColumnValidator('notify_email', v::Optional(v::Email()), true);
        $v->addColumnValidator('ice_email_address', v::Optional(v::Email()), true);
        //Add in some details from the badge_type
        if (isset($badgetypeData['price'])) {
            $item['payment_badge_price'] = $badgetypeData['price'];
        }
        if (isset($badgetypeData['price_per_applicant'])) {
            $item['payment_badge_price'] = $badgetypeData['price_per_applicant'];
        }

        //Addons validation
        if (isset($item['addons'])) {
            $availableaddons = array_column($this->GetAddonsAvailable($item['badge_type_id'], $item['context_code']), null, 'id');

            $v->addColumnValidator('addons', v::ArrayVal()->key('addon_id', v::in(array_keys($availableaddons)), false), true);
            foreach ($item['addons'] as &$addon) {
                if (!isset($availableaddons[$addon['addon_id'] ?? null])) {
                    //Wat this should have been verified?
                    $addon['err'] = 'Not found this addon?';
                    continue;
                }
                $faddon = $availableaddons[$addon['addon_id']];
                $addon['payment_price'] = $faddon['price'];
            }
        }

        //TODO: Validate form questions?
    }

    //Copied from badgeinfo
    //TODO: Maybe switch to using that class entirely?

    public function GetAddonsAvailable($badge_type_id, $context_code)
    {
        switch ($context_code) {
                case 'A':
                    return $this->GetAttendeeAddonsAvailable($badge_type_id);
                case 'S':
                    return [];
                default:
                    return $this->GetApplicationAddonsAvailable($badge_type_id);
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
    public function GetApplicationAddonsAvailable($badge_type_id)
    {
        return $this->g_addon->Search(
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
                       $this->g_addonmap,
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
    public function GetValidTypeIdsForContext($context_code)
    {
        switch ($context_code) {
            case 'A':
                return array_column($this->a_badge_type->Search(
                    array('id'),
                    array(
                        $this->CurrentUserInfo->EventIdSearchTerm(),
                        new SearchTerm('active', 1)
                    )
                ), 'id');
            case 'S':
                return array_column($this->s_badge_type->Search(
                    array('id'),
                    array(
                        $this->CurrentUserInfo->EventIdSearchTerm(),
                        new SearchTerm('active', 1)
                    )
                ), 'id');
            default:
                return array_column($this->g_badge_type->Search(
                    new View(
                        array('id'),
                        array(new Join(
                            $this->g_group,
                            array(
                              'id' => 'group_id',
                            ),
                            alias:'grp'
                        ))
                    ),
                    array(
                        new SearchTerm('active', 1),
                        new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId(), JoinedTableAlias: 'grp'),
                        new SearchTerm('context_code', $context_code, JoinedTableAlias: 'grp')
                    )
                ), 'id');
        }
    }
}
