<?php

namespace CM3_Lib\models\application;

use CM3_Lib\database\Column as cm_Column;

class submission extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Submissions';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'badge_type_id'		=> new cm_Column('INT', null, false),
            'contact_id'	=> new cm_Column('BIGINT', null, false, false, false, false),
            'uuid_raw'		=> new cm_Column('BINARY', 16, false, false, true, false, '(UUID_TO_BIN(UUID()))'),
            'uuid'			=> new cm_Column('CHAR', 36, null, false, false, false, null, false, 'GENERATED ALWAYS as (BIN_TO_UUID(`uuid_raw`)) VIRTUAL'),
            'display_id'	=> new cm_Column('INT', null, true),
            'hidden'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),

            'real_name'		=> new cm_Column('VARCHAR', '500', false),
            'fandom_name'	=> new cm_Column('VARCHAR', '255', true),
            'name_on_badge'	=> new cm_Column(
                'ENUM',
                array(
                    'Fandom Name Large, Real Name Small',
                    'Real Name Large, Fandom Name Small',
                    'Fandom Name Only',
                    'Real Name Only'
                ),
                false,
                defaultValue: "'Real Name Only'"
            ),
            'applicant_count' => new cm_Column('INT', null, false, defaultValue: '0'),
            'assignment_count' => new cm_Column('INT', null, false, defaultValue: '0'),
            'application_status'		=> new cm_Column(
                'ENUM',
                array(
                    'InProgress',
                    'Submitted',
                    'Cancelled',
                    'Rejected',
                    'PendingAcceptance',
                    'Accepted',
                    'Waitlisted',
                ),
                false
            ),

            /* Payment Info */
        'payment_badge_price'	=> new cm_Column('DECIMAL', '7,2', false),
        'payment_promo_code' 	=> new cm_Column('VARCHAR', '255', true),
        'payment_promo_price'	=> new cm_Column('DECIMAL', '7,2', true),
        'payment_id'		=> new cm_Column('BIGINT', null, true),
        'payment_id_hist'	=> new cm_Column('VARCHAR', 740, true),
        'payment_status'		=> new cm_Column(
            'ENUM',
            array(
                'NotStarted',
                'Incomplete',
                'Cancelled',
                'Rejected',
                'Completed',
                'Refunded',
                'RefundedInPart',
            ),
            false,
            defaultValue: '\'NotStarted\''
        ),

            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),
            //Generated columns

        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','application_name1','application_name2','display_id','application_status');
    }

    //TODO: Fix up
    public function generate_invoice($application, $atdb = null)
    {
        $ctx_info = $this->ctx_info;
        if (!$ctx_info) {
            return false;
        }
        $badge = $this->get_badge_type($application['badge-type-id']);
        if (!$badge) {
            return false;
        }

        $applications = array();
        $assignments = array();
        $applicants = array();
        $discounts = array();

        $applications[] = array(
            'application-id' => $application['id'],
            'name' => $ctx_info['nav_prefix'] . ' Application Fee',
            'details' => $badge['name'],
            'price' => $badge['base-price'],
            'price-string' => price_string($badge['base-price'])
        );

        $free_assignments = $badge['base-assignment-count'];
        if (isset($application['assigned-rooms-and-tables']) && $application['assigned-rooms-and-tables']) {
            $count = count($application['assigned-rooms-and-tables']);
            foreach ($application['assigned-rooms-and-tables'] as $index => $art) {
                $assignments[] = array(
                    'application-id' => $application['id'],
                    'name' => $ctx_info['nav_prefix'] . ' ' . $ctx_info['assignment_term'][0] . ' Fee',
                    'details' => $art['room-or-table-id'] . ' (' . ($index + 1) . ' of ' . $count . ')',
                    'price' => ($index < $free_assignments) ? 0 : $badge['price-per-assignment'],
                    'price-string' => ($index < $free_assignments) ? 'INCLUDED' : price_string($badge['price-per-assignment'])
                );
            }
        } else {
            $count = $application['assignment-count'];
            if ((float)$badge['price-per-assignment']) {
                for ($index = 0; $index < $count; $index++) {
                    $assignments[] = array(
                        'application-id' => $application['id'],
                        'name' => $ctx_info['nav_prefix'] . ' ' . $ctx_info['assignment_term'][0] . ' Fee',
                        'details' => '(' . ($index + 1) . ' of ' . $count . ')',
                        'price' => ($index < $free_assignments) ? 0 : $badge['price-per-assignment'],
                        'price-string' => ($index < $free_assignments) ? 'INCLUDED' : price_string($badge['price-per-assignment'])
                    );
                }
            }
        }

        $free_applicants = $badge['base-applicant-count'] * $count;
        if (isset($application['applicants']) && $application['applicants']) {
            $count = count($application['applicants']);
            foreach ($application['applicants'] as $index => $applicant) {
                $applicants[] = array(
                    'application-id' => $application['id'],
                    'name' => $ctx_info['nav_prefix'] . ' Badge Fee',
                    'details' => $applicant['display-name'] . ' (' . ($index + 1) . ' of ' . $count . ')',
                    'price' => ($index < $free_applicants) ? 0 : $badge['price-per-applicant'],
                    'price-string' => ($index < $free_applicants) ? 'INCLUDED' : price_string($badge['price-per-applicant'])
                );
            }
        } else {
            $count = $application['applicant-count'];
            if ((float)$badge['price-per-applicant']) {
                for ($index = 0; $index < $count; $index++) {
                    $applicants[] = array(
                        'application-id' => $application['id'],
                        'name' => $ctx_info['nav_prefix'] . ' Badge Fee',
                        'details' => '(' . ($index + 1) . ' of ' . $count . ')',
                        'price' => ($index < $free_applicants) ? 0 : $badge['price-per-applicant'],
                        'price-string' => ($index < $free_applicants) ? 'INCLUDED' : price_string($badge['price-per-applicant'])
                    );
                }
            }
        }

        if (isset($application['applicants']) && $application['applicants'] && $atdb) {
            $name_map = $atdb->get_badge_type_name_map();
            $fdb = new cm_forms_db($this->cm_db, 'application');

            $total_price = 0;
            foreach ($applications as $a) {
                $total_price += $a['price'];
            }
            foreach ($assignments as $a) {
                $total_price += $a['price'];
            }
            foreach ($applicants as $a) {
                $total_price += $a['price'];
            }

            $max_discount = 0;
            switch ($badge['max-prereg-discount']) {
                case 'Price per Applicant': $max_discount = $badge['price-per-applicant' ]; break;
                case 'Price per Assignment': $max_discount = $badge['price-per-assignment']; break;
                case 'Total Price': $max_discount = $total_price                  ; break;
            }

            foreach ($application['applicants'] as $applicant) {
                if (isset($applicant['application-id']) && $applicant['application-id']) {
                    $application = $atdb->get_application($applicant['application-id'], false, $name_map, $fdb);
                    if ($application && $application['payment-status'] == 'Completed') {
                        $discount = min($application['payment-txn-amt'], $max_discount, $total_price);
                        if ($discount > 0) {
                            $discounts[] = array(
                                'application-id' => $application['id'],
                                'name' => 'Application Preregistration Discount',
                                'details' => $application['display-name'],
                                'price' => -$discount,
                                'price-string' => '-' . price_string($discount)
                            );
                            $total_price -= $discount;
                        }
                    }
                }
            }
        }

        return array_merge($applications, $assignments, $applicants, $discounts);
    }
}
