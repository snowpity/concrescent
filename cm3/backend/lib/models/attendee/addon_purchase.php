<?php

namespace CM3_Lib\models\attendee;

use CM3_Lib\database\Column as cm_Column;

class addon_purchase extends CM3_Lib\database\Table
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
