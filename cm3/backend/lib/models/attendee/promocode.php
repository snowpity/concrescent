<?php

namespace CM3_Lib\models\attendee;

use CM3_Lib\database\Column as cm_Column;

class promocode extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Attendee_Promo_Codes';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false, false, false, true),
            'valid_badge_type_ids' => new cm_Column('TEXT', null, true),
            'is_percentage' => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
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
