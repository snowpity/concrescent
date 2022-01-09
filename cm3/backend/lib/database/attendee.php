<?php

require_once dirname(__FILE__).'/database.php';
require_once dirname(__FILE__).'/eventinfo.php';

class cm_attendee_db extends cm_Table
{
    protected cm_eventinfo_db $eventinfo_db;
    protected cm_attendee_badge_types_db $badgetypes_db;
    protected function setupTableDefinitions(): void
    {
        $this->eventinfo_db = new cm_eventinfo_db($this->cm_db);
        $this->badgetypes_db = new cm_attendee_badge_types_db($this->cm_db);
        $this->TableName = 'Attendees';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null, true),
            'badge_type_id'	=> new cm_Column('INT', null, false, false, false, false),
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
            'can_transfer'	=> new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'ice_name'			=> new cm_Column('VARCHAR', '255', true),
            'ice_relationship'	=> new cm_Column('VARCHAR', '255', true),
            'ice_email_address'	=> new cm_Column('VARCHAR', '255', true),
            'ice_phone_number'	=> new cm_Column('VARCHAR', '255', true),
            'time_printed'		=> new cm_Column('TIMESTAMP', null, true),
            'time_checked_in'	=> new cm_Column('TIMESTAMP', null, true),

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
            'notes'			=> new cm_Column('TEXT', null, true)
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','display_id','first_name','last_name','notify_email');
        $this->Views = array(
            'default' => new cm_View(
                array(
                        new cm_SelectColumn('display_id', EncapsulationFunction: 'concat(\'A\' , ?)', Alias: 'ID'),
                        new cm_SelectColumn('real_name'),
                        new cm_SelectColumn('fandom_name'),
                        new cm_SelectColumn('name', Alias: 'Badge Type', JoinedTableAlias: 'bt'),
                        new cm_SelectColumn('notify_email'),
                        new cm_SelectColumn('payment_status'),
                        new cm_SelectColumn('payment_promo_code'),
                        new cm_SelectColumn('time_printed'),
                        new cm_SelectColumn('time_checked_in')
                    ),
                array(
                       new cm_Join(
                           $this->badgetypes_db,
                           array('badge_type_id'=>'id'),
                           'INNER',
                           alias: 'bt',
                           subQSelectColumns: array(
                               new cm_SelectColumn('id'),
                               new cm_SelectColumn('name')
                           ),
                           subQSearchTerms: array(
                               $this->eventinfo_db->GetSearchTerm()
                           )
                       )
                    )
            )
        );
    }
}
class cm_attendee_badge_types_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Attendee_Badge_Types';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false, false, false, true),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'display_order' => new cm_Column('INT', null, false),
            'name'          => new cm_Column('VARCHAR', '255', false),
            'description'   => new cm_Column('TEXT', null, true),
            'rewards'       => new cm_Column('TEXT', null, true),
            'price'         => new cm_Column('DECIMAL', '7,2', false),
            'payable_onsite'=> new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'quantity'      => new cm_Column('INT', null, true),
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
class cm_attendee_promo_codes_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Attendee_Promo_Codes';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false, false, false, true),
            'valid_badge_type_ids' => new cm_Column('TEXT', null, true),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'code'          => new cm_Column('VARCHAR', '255', false, false, true, true),
            'description'   => new cm_Column('TEXT', null, true),
            'price'         => new cm_Column('DECIMAL', '7,2', false),
            'quantity'      => new cm_Column('INT', null, true),
            'start_date'	=> new cm_Column('DATE', null, false),
            'end_date'  	=> new cm_Column('DATE', null, false),
            'limit_per_customer' => new cm_Column('INT', null, true),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),
            //Generated columns
            'dates_available' => new cm_Column('VARCHAR', '50', null, customPostfix: 'GENERATED ALWAYS as (concat(case `start_date` is null when true then \'forever\' else `start_date` end,\' to \', case end_date is null when true then \'forever\' else `end_date` end)) VIRTUAL'),
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','code','price','quantity','dates_available');
    }
}
class cm_attendee_addons_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Attendee_Addons';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false, false, false, true),
            'valid_badge_type_ids' => new cm_Column('TEXT', null, true),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'display_order' => new cm_Column('INT', null, false),
            'name'          => new cm_Column('VARCHAR', '255', false),
            'description'   => new cm_Column('TEXT', null, true),
            'rewards'       => new cm_Column('TEXT', null, true),
            'price'         => new cm_Column('DECIMAL', '7,2', false),
            'payable_onsite'=> new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'quantity'      => new cm_Column('INT', null, true),
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
class cm_attendee_addon_purchases_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Attendee_Addon_Purchases';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null, true),
            'attendee_id'	=> new cm_Column('BIGINT', null, false, false, false, true),
            'addon_id'		=> new cm_Column('INT', null, false, false, false, true),
            'payment_price'	=> new cm_Column('DECIMAL', '7,2', false),
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
            'notes'			=> new cm_Column('TEXT', null, true)
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','attendee_id','addon_id','payment_status','dates_available');
    }
}