<?php

namespace CM3_Lib\models\application;

use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\ColumnIndex;

class submissionapplicant extends \CM3_Lib\database\Table
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
                false,
                defaultValue: "'Real Name Only'"
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
        $this->IndexDefs = array('ft_name' => new ColumnIndex(array(
            'real_name' =>false,
            'fandom_name'=>false,
            'notify_email'=>false,
            'ice_name'=>false,
            'ice_email_address'=>false
        ), 'fulltext'));
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','display_id','real_name','notify_email');
    }
}
