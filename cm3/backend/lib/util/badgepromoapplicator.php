<?php

namespace CM3_Lib\util;

use Respect\Validation\Validator as v;
use CM3_Lib\database\TableValidator;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\SelectColumn;

use CM3_Lib\models\attendee\badgetype;
use CM3_Lib\models\attendee\badge;
use CM3_Lib\models\attendee\promocode;

final class badgepromoapplicator
{
    public function __construct(
        private badgetype $badgetype,
        private badge $badge,
        private promocode $promocode,
        private CurrentUserInfo $CurrentUserInfo
    ) {
    }

    private array $loadedPromoCodes = array();
    private array $applicableIDs = array();
    public function LoadCode(string $code): bool
    {
        $code = strtoupper($code);
        //Do we already have it loaded?
        if (isset($this->loadedPromoCode[$code])) {
            return $this->loadedPromoCode[$code]!== false;
        }

        $foundCode = $this->promocode->Search(
            new View(array(
            'valid_badge_type_ids',
            'is_percentage',
            'discount',
            'start_date',
            'end_date',
            'limit_per_customer',
            'quantity'
        ), ),
            array(
            new SearchTerm('code', $code),
            new SearchTerm('Active', 1),
            $this->CurrentUserInfo->EventIdSearchTerm()
        )
        );
        //Did we find that code?
        if ($foundCode !== false && count($foundCode) >0) {
            $foundCode = $foundCode[0];
            $this->applicableIDs[$code] =array_diff(explode(',', $foundCode['valid_badge_type_ids']), array(""));
            //Count up how many times this code has been used
            if (!empty($foundCode['quantity'])) {
                $usedCounts = $this->badge->Search(
                    new View(
                        array(new SelectColumn('id', false, 'COUNT(?)', 'UsedQuantity')),
                        array(
                            new Join(
                                $this->badgetype,
                                array('id'=> 'badge_type_id',
                                    new SearchTerm('event_id', $this->CurrentUserInfo->GetEventId())
                                ),
                                alias:'typ'
                            )
                        )
                    ),
                    array(
                        new SearchTerm('payment_promo_code', $code),
                        new SearchTerm('payment_status', 'Completed'),
                    )
                );
                if (count($usedCounts) > 0) {
                    $foundCode['used'] = $usedCounts[0]['UsedQuantity'];
                }
            }
            $this->loadedPromoCode[$code] = $foundCode;
            return true;
        } else {
            $this->loadedPromoCode[$code] = false;
            $this->applicableIDs[$code] = array();
            return false;
        }
    }

    public function TryApplyCode(&$item, $code): bool
    {
        if (!empty($code)) {
            $code = strtoupper($code);
            //First, does this badge match our type?
            if ($item['context_code'] != 'A') {
                return false;
            }
            if (empty($code)) {
                $this->resetCode($item);
                return false;
            }
            //Did we load this code?
            if (!$this->LoadCode($code)) {
                if (isset($item['payment_promo_code']) && $code != $item['payment_promo_code']) {
                    //Re-apply the one they theoretically have already
                    $this->TryApplyCode($item, $item['payment_promo_code']);
                } else {
                    $this->resetCode($item);
                }
                return false;
            }

            //Does this badge apply?
            if (
                !in_array($item['badge_type_id'], $this->applicableIDs[$code])
                && count($this->applicableIDs[$code])>0) {
                if (isset($item['payment_promo_code']) && $code != $item['payment_promo_code']) {
                    //Re-apply the one they theoretically have already
                    $this->TryApplyCode($item, $item['payment_promo_code']);
                } else {
                    $this->resetCode($item);
                }
                return false;
            }

            //Initial quote
            $promo_code = $this->loadedPromoCode[$code];
        } else {
            $promo_code = array(
                'code'=>null,
                'is_percentage'=>0,
                'discount'=>0
            );
        }

        $badge_price = (float)$item['payment_badge_price'];
        $promo_price = (float)$promo_code['discount'];
        $final_price = (
            $promo_code['is_percentage']
            ? ($badge_price * (100.0 - $promo_price) / 100.0)
            : ($badge_price - $promo_price)
        );

        //Are we editing a badge?
        if (isset($item['existing'])) {
            // $existingBadge = $this->badge->GetByID($item['id'], array('uuid','payment_badge_price'));
            // if ($existingBadge !== false && $item['uuid'] == $existingBadge['uuid']) {
            //     $badge_price = max(0, $badge_price- $existingBadge['payment_badge_price']);
            // }
            $badge_price = max(0, $badge_price- (float)$item['existing']['payment_badge_price']);
        }
        if ($final_price < 0) {
            $final_price = 0;
        }
        if ($final_price > $badge_price) {
            $final_price = $badge_price;
        }


        //Only apply promo if it actually results in a price reduction or equality
        if ((isset($item['payment_promo_price']) && $item['payment_promo_price'] >= $final_price) || !isset($item['payment_promo_price'])) {
            $item['payment_promo_code'] = $code;
            $item['payment_promo_price'] = $final_price;
            //For display purposes
            $item['payment_promo_type'] = $promo_code['is_percentage'] ? 1 : 0;
            $item['payment_promo_amount'] = $promo_price;
        } else {
            $item['payment_promo_price'] = $final_price;
        }

        //Do it for addons too, if specified
        if (isset($item['addons'])) {
            foreach ($item['addons'] as &$addon) {
                //Haha sike we don't do promos (yet)
                $addon['payment_promo_code'] = null;
                $addon['payment_promo_price'] = $addon['payment_price'] ?? 0;
            }
        }

        return true;
    }

    private function resetCode(&$item)
    {
        //Ensure the promo price and code are un-set
        unset($item['payment_promo_code']);
        if (isset($item['payment_badge_price'])) {
            $item['payment_promo_price'] = $item['payment_badge_price'];
        } else {
            unset($item['payment_promo_price']);
        }
    }
}
