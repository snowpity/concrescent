<?php

namespace CM3_Lib\models\staff;

use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\ColumnIndex;
use CM3_Lib\database\SelectColumn as cm_SelectColumn;
use CM3_Lib\database\View as cm_View;
use CM3_Lib\database\Join as cm_Join;

class badge extends \CM3_Lib\database\Table
{
    protected \CM3_Lib\models\eventinfo $eventinfo_db;
    protected \CM3_Lib\models\staff\badgetype $staff_badgetypes_db;
    protected function setupTableDefinitions(): void
    {
        $this->eventinfo_db = new \CM3_Lib\models\eventinfo($this->cm_db);
        $this->staff_badgetypes_db = new \CM3_Lib\models\staff\badgetype($this->cm_db);
        $this->TableName = 'Staff_Badges';
        $this->ColumnDefs = array(
            'id'            => new cm_Column('BIGINT', null, false, true, false, true, null, true),
            'badge_type_id' => new cm_Column('INT', null, false, false, false, false),
            'contact_id'    => new cm_Column('BIGINT', null, false, false, false, false),
            'display_id'    => new cm_Column('INT', null, true),
            'hidden'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'uuid_raw'      => new cm_Column('BINARY', 16, false, false, true, false, '(UUID_TO_BIN(UUID()))'),
            'uuid'          => new cm_Column('CHAR', 36, null, false, false, false, null, false, 'GENERATED ALWAYS as (BIN_TO_UUID(`uuid_raw`)) VIRTUAL'),
            'real_name'     => new cm_Column('VARCHAR', '500', false),
            'fandom_name'   => new cm_Column('VARCHAR', '255', true),
            'name_on_badge' => new cm_Column(
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
            'date_of_birth'      => new cm_Column('DATE', null, false),
            'notify_email'	=> new cm_Column('VARCHAR', '255', true),
            'ice_name'           => new cm_Column('VARCHAR', '255', true),
            'ice_relationship'   => new cm_Column('VARCHAR', '255', true),
            'ice_email_address'  => new cm_Column('VARCHAR', '255', true),
            'ice_phone_number'   => new cm_Column('VARCHAR', '255', true),
            'time_printed'       => new cm_Column('TIMESTAMP', null, true),
            'time_checked_in'    => new cm_Column('TIMESTAMP', null, true),

            'application_status' => new cm_Column(
                'ENUM',
                array(
                    'InProgress', //Draft
                    'Submitted',  //Newly submitted
                    'Cancelled',  //Applicant self-cancelled
                    'Rejected',   //Staff rejected
                    'PendingAcceptance', //Accepted, waiting for them to confirm
                    'Waitlisted', //Waitlisted for consideration
                    'Onboarding', //Accepted, onboarding in progress
                    'Active',     //Accepted, active staff
                    'Terminated', //No longer welcome
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
            'notes'			=> new cm_Column('TEXT', null, true)
        );
        $this->IndexDefs = array('ft_name' => new ColumnIndex(array(
            'real_name' =>false,
            'fandom_name'=>false,
            'notify_email'=>false,
            'ice_name'=>false,
            'ice_email_address'=>false
        ), 'fulltext'));
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','display_id','first_name','last_name');
        $this->Views = array(
            'default' => new cm_View(
                array(
                        new cm_SelectColumn('display_id', EncapsulationFunction: 'concat(\'S\' , ?)', Alias: 'ID'),
                        new cm_SelectColumn('real_name'),
                        new cm_SelectColumn('fandom_name'),
                        new cm_SelectColumn('name', Alias: 'Badge Type', JoinedTableAlias: 'bt'),
                        new cm_SelectColumn('application_status'),
                        new cm_SelectColumn('payment_status'),
                        new cm_SelectColumn('time_printed'),
                        new cm_SelectColumn('time_checked_in')
                    ),
                array(
                       new cm_Join(
                           $this->staff_badgetypes_db,
                           array('id'=>'badge_type_id'),
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
