<?php

namespace CM3_Lib\models;

use CM3_Lib\database\Column as cm_Column;

class payment extends CM3_Lib\database\Table
{
    //TODO: Maybe make this public static so other classes don't need to repeat it?
    public $payment_statuses = array(
        'NotStarted',
        'Incomplete',
        'Cancelled',
        'Rejected',
        'Completed',
        'Refunded',
        'RefundedInPart',
    );
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Payments';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false, false, false, true),
            'uuid_raw'		=> new cm_Column('BINARY', 16, false, false, true, false, '(UUID_TO_BIN(UUID()))'),
            'requested_by'			=> new cm_Column('VARCHAR', '255', true),
            'contact_id'	=> new cm_Column('BIGINT', null, false, false, false, false),
            'items'			=> new cm_Column('TEXT', null, true),
            'mail_template'			=> new cm_Column('VARCHAR', '255', true),

            'payment_system'			=> new cm_Column('VARCHAR', '255', true),
            'payment_txn_id'			=> new cm_Column('CHAR', 36, null, false, false, false, null, false, 'GENERATED ALWAYS as (BIN_TO_UUID(`uuid_raw`)) VIRTUAL'),
            'payment_txn_amt'		=> new cm_Column('DECIMAL', '7,2', false),
            'payment_date'			=> new cm_Column('TIMESTAMP', null, true),
            'payment_details'		=> new cm_Column('TEXT', null, true),
            'payment_status'	=> new cm_Column('ENUM', $this->payment_statuses, false),

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
