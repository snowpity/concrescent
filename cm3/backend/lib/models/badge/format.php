<?php

namespace CM3_Lib\models\badge;

use CM3_Lib\database\Column as cm_Column;

class format extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName  = 'Badge_Formats';
        $this->ColumnDefs = array(
            'id'             => new cm_Column('SMALLINT', null, false, true, false, true, null, true),
            'event_id'       => new cm_Column('INT', null, false, false, false, true),
            'name'           => new cm_Column('VARCHAR', '255', false),
            'bgImageID'      => new cm_Column('BIGINT', null, true, false, false, false),
            'customSize'     => new cm_Column('VARCHAR', '255', true, defaultValue: null),
            'layoutPosition' => new cm_Column('VARCHAR', '255', true, defaultValue: null),
            'layout'         => new cm_Column('TEXT', null, true),
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','event_id','name','bgImageID','customSize');
    }

    public function verifyFormatBelongsToEvent(int $id, int $event_id)
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
