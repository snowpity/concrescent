<?php

namespace CM3_Lib\models\application;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\ColumnIndex as cm_ColumnIndex;

class addonmap extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Addon_Maps';
        $this->ColumnDefs = array(
            'badge_type_id'	=> new cm_Column('INT', null, false),
            'addon_id'	=> new cm_Column('INT', null, false)
        );
        $this->IndexDefs = array(
            'primary' => new cm_ColumnIndex(array(
                'badge_type_id' => false,
                'addon_id' => false,
            ), 'primary key')
        );
        $this->PrimaryKeys = array();
        $this->DefaultSearchColumns = array('addon_id');
    }

    public function getBadgeTypesForAddon($addon_id)
    {
        return  implode(
            ',',
            array_column(
                $this->Search(['badge_type_id'], [new SearchTerm('addon_id', $addon_id)]),
                'badge_type_id'
            )
        );
    }
    public function setBadgeTypesForAddon($addon_id, $valid_badge_type_ids)
    {
        $currentIDs = array_column(
            $this->Search(['badge_type_id'], [new SearchTerm('addon_id', $addon_id)]),
            'badge_type_id'
        );
        //Process adds
        foreach (array_diff($valid_badge_type_ids, $currentIDs) as $newID) {
            $this->Create(['addon_id'=>$addon_id,'badge_type_id'=>$newID]);
        }

        //Process removes
        foreach (array_diff($currentIDs, $valid_badge_type_ids) as $delID) {
            $this->Delete(['addon_id'=>$addon_id,'badge_type_id'=>$delID]);
        }
    }
}
