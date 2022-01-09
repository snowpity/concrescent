<?php

require_once dirname(__FILE__).'/database.php';
require_once dirname(__FILE__).'/eventinfo.php';

class cm_application_groups_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Groups';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false),
            'context_code'	=> new cm_Column('VARCHAR', '3', false),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'true'),
            'can_assign'    => new cm_Column('BOOLEAN', null, false, defaultValue: 'true'), //Whether applications in the group can be assigned a location/time slot
            'order'					=> new cm_Column('TINYINT', null, false),
            'name'          => new cm_Column('VARCHAR', '255', false),
            'menu_icon'     => new cm_Column('VARCHAR', '255', true),
            'description'   => new cm_Column('TEXT', null, true),
            'appplication_name1'          => new cm_Column('VARCHAR', '255', false),
            'appplication_name2'          => new cm_Column('VARCHAR', '255', true),

            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),

        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','context_code','name','menu_icon');
    }
}


class cm_application_locations_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Locations';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false),
            'short_code'	=> new cm_Column('VARCHAR', '10', false),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'true'),
            'name'          => new cm_Column('VARCHAR', '255', false),
            'description'   => new cm_Column('TEXT', null, true),

            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),

        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','short_code','name');
    }
}

class cm_application_location_maps_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Location_Maps';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'true'),
            'bgImageID'		=> new cm_Column('BIGINT', null, false, false, false, false),
            'name'          => new cm_Column('VARCHAR', '255', false),
            'description'   => new cm_Column('TEXT', null, true),

            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),

        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','bgImageID','name');
    }
}


class cm_application_location_coords_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Location_Coords';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'map_id'		=> new cm_Column('INT', null, false),
            'location_id'		=> new cm_Column('INT', null, false),
            'coords'        => new cm_Column('VARCHAR', '255', false),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),

        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','map_id','location_id','coords');
    }
}

class cm_application_assignments_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Assignments';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'application_id'		=> new cm_Column('INT', null, false),
            'location_id'		=> new cm_Column('INT', null, false),
            'start_time'    => new cm_Column('DATETIME', null, false),
            'end_time'      => new cm_Column('DATETIME', null, false),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),

        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','application_id','location_id','start_time','end_time');
    }
}

class cm_application_types_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Types';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'group_id'		=> new cm_Column('INT', null, false),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
      'display_order' => new cm_Column('INT', null, false),
      'name'          => new cm_Column('VARCHAR', '255', false),
      'description'   => new cm_Column('TEXT', null, true),
      'rewards'       => new cm_Column('TEXT', null, true),
      'max_applicant_count' => new cm_Column('INT', null, false),
      'max_assignment_count' => new cm_Column('INT', null, false),
      'base_price'         => new cm_Column('DECIMAL', '7,2', false),

      'base_applicant_count' => new cm_Column('INT', null, false),
      'base_assignment_count' => new cm_Column('INT', null, false),
      'price_per_applicant'         => new cm_Column('DECIMAL', '7,2', false),
      'price_per_assignment'         => new cm_Column('DECIMAL', '7,2', false),

            'max_prereg_discount'		=> new cm_Column(
                'ENUM',
                array(
                    'No Discount',
                    'Price per Applicant',
                    'Price per Assignment',
                    'Total Price',
                ),
                false
            ),
      'payable_onsite'=> new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
      'max_total_applications' => new cm_Column('INT', null, false, defaultValue: '0'),
      'max_total_applicants' => new cm_Column('INT', null, false, defaultValue: '0'),
      'max_total_assignments' => new cm_Column('INT', null, false, defaultValue: '0'),
      'start_date'	=> new cm_Column('DATE', null, false),
      'end_date'  	=> new cm_Column('DATE', null, false),
      'min_age'   	=> new cm_Column('INT', null, true),
      'max_age'     	=> new cm_Column('INT', null, true),
      'active_override_code' => new cm_Column('VARCHAR', '255', true),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),
            //Generated columns
            'dates_available' => new cm_Column('VARCHAR', '50', null, customPostfix: 'GENERATED ALWAYS as (concat(case `start_date` is null when true then \'forever\' else `start_date` end,\' to \', case end_date is null when true then \'forever\' else `end_date` end)) VIRTUAL'),
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','name','price','quantity','dates_available');
    }
}


class cm_application_submissions_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Submissions';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'type_id'		=> new cm_Column('INT', null, false),
            'contact_id'	=> new cm_Column('BIGINT', null, false, false, false, false),
            'uuid_raw'		=> new cm_Column('BINARY', 16, false, false, true, false, '(UUID_TO_BIN(UUID()))'),
            'uuid'			=> new cm_Column('CHAR', 36, null, false, false, false, null, false, 'GENERATED ALWAYS as (BIN_TO_UUID(`uuid_raw`)) VIRTUAL'),
            'display_id'	=> new cm_Column('INT', null, true),
            'hidden'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),

      'appplication_name1'          => new cm_Column('VARCHAR', '255', false),
      'appplication_name2'          => new cm_Column('VARCHAR', '255', false),
      'applicant_count' => new cm_Column('INT', null, false),
      'assignment_count' => new cm_Column('INT', null, false),
            'application_status'		=> new cm_Column(
                'ENUM',
                array(
                    'Incomplete',
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
        'payment_promo_price'	=> new cm_Column('DECIMAL', '7,2', false),
        'payment_txn_id'		=> new cm_Column('CHAR', 36, null, customPostfix: 'CHARACTER SET ascii'),
        'payment_txn_id_hist'	=> new cm_Column('VARCHAR', 740, null, customPostfix: 'CHARACTER SET ascii'),
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
            false
        ),

            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),
            //Generated columns
            'dates_available' => new cm_Column('VARCHAR', '50', null, customPostfix: 'GENERATED ALWAYS as (concat(case `start_date` is null when true then \'forever\' else `start_date` end,\' to \', case end_date is null when true then \'forever\' else `end_date` end)) VIRTUAL'),
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','name','price','quantity','dates_available');
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
            $fdb = new cm_forms_db($this->cm_db, 'attendee');

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
                if (isset($applicant['attendee-id']) && $applicant['attendee-id']) {
                    $attendee = $atdb->get_attendee($applicant['attendee-id'], false, $name_map, $fdb);
                    if ($attendee && $attendee['payment-status'] == 'Completed') {
                        $discount = min($attendee['payment-txn-amt'], $max_discount, $total_price);
                        if ($discount > 0) {
                            $discounts[] = array(
                                'application-id' => $application['id'],
                                'name' => 'Attendee Preregistration Discount',
                                'details' => $attendee['display-name'],
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


class cm_application_submission_applicants_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Submission_Applicants';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null, true),
            'application_id'	=> new cm_Column('INT', null, false, false, false, false),
            'contact_id'	=> new cm_Column('BIGINT', null, false, false, false, false),
            'display_id'	=> new cm_Column('INT', null, true),
            'hidden'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'uuid_raw'		=> new cm_Column('BINARY', 16, false, false, true, false, '(UUID_TO_BIN(UUID()))'),
            'uuid'			=> new cm_Column('CHAR', 36, null, false, false, false, null, false, 'GENERATED ALWAYS as (BIN_TO_UUID(`uuid_raw`)) VIRTUAL'),
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
                false
            ),
            'date_of_birth'	=> new cm_Column('DATE', null, false),
            'notify_email'	=> new cm_Column('VARCHAR', '255', true),
            'can_transfer'	=> new cm_Column('BOOLEAN', null, false, defaultValue: 'true'),
            'ice_name'			=> new cm_Column('VARCHAR', '255', true),
            'ice_relationship'	=> new cm_Column('VARCHAR', '255', true),
            'ice_email_address'	=> new cm_Column('VARCHAR', '255', true),
            'ice_phone_number'	=> new cm_Column('VARCHAR', '255', true),
            'time_printed'		=> new cm_Column('TIMESTAMP', null, true),
            'time_checked_in'	=> new cm_Column('TIMESTAMP', null, true),

            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true)
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','display_id','first_name','last_name','notify_email');
    }
}
