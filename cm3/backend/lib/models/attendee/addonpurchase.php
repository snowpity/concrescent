<?php

namespace CM3_Lib\models\attendee;

use CM3_Lib\database\Column;
use CM3_Lib\database\ColumnIndex;

class addonpurchase extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Attendee_Addon_Purchases';
        $this->ColumnDefs = array(
            'attendee_id'	=> new Column('BIGINT', null, false, false, false, true),
            'addon_id'		=> new Column('INT', null, false, false, false, true),
            'payment_price'	=> new Column('DECIMAL', '7,2', false),
            'payment_promo_code' 	=> new Column('VARCHAR', '255', true),
            'payment_promo_price'	=> new Column('DECIMAL', '7,2', false),
            'payment_id'		=> new Column('BIGINT', null, true),
            'payment_id_hist'	=> new Column('VARCHAR', 740, true),
            'payment_status'		=> new Column(
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

            'date_created'	=> new Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new Column('TEXT', null, true)
        );

        $this->IndexDefs = array(
            'primary' => new ColumnIndex(array(
                'attendee_id' => false,
                'addon_id' => false,
            ), 'primary key')
        );
        $this->PrimaryKeys = array('attendee_id'=>false,'addon_id'=>false);
        $this->DefaultSearchColumns = array('id','attendee_id','addon_id','payment_status','dates_available');
    }
}
