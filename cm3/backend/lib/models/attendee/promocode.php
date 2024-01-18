<?php

namespace CM3_Lib\models\attendee;

use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\ColumnIndex as cm_ColumnIndex;

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
            'code'          => new cm_Column('VARCHAR', '255', false, false, false, false),
            'description'   => new cm_Column('TEXT', null, true),
            'discount'         => new cm_Column('DECIMAL', '7,2', false),
            'quantity'      => new cm_Column('INT', null, true),
            'start_date'	=> new cm_Column('DATE', null, true),
            'end_date'  	=> new cm_Column('DATE', null, true),
            'limit_per_customer' => new cm_Column('INT', null, true),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),
            //Generated columns
            'dates_available' => new cm_Column('VARCHAR', '50', null, customPostfix: 'GENERATED ALWAYS as (concat(case `start_date` is null when true then \'forever\' else `start_date` end,\' to \', case end_date is null when true then \'forever\' else `end_date` end)) VIRTUAL'),
        );
        $this->IndexDefs = array('code_event_id' => new cm_ColumnIndex(['event_id','code'],'unique'));
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','code','active','discount','is_percentage','quantity','dates_available');
    }

    public function verifyPromoCodeBelongsToEvent(int $id, int $event_id)
    {
        $bt = $this->GetByID($id, array('event_id'));
        if ($bt === false) {
            return false;
        }
        if ($bt['event_id'] != $event_id) {
            return false;
        }
        return true;
    }
}
