<?php

namespace CM3_Lib\models\badge;

use \CM3_Lib\database\Column as cm_Column;

class format extends CM3_Lib\database\Table {
	protected function setupTableDefinitions() : void
	{
		$this->TableName  = 'Badge_Formats';
		$this->ColumnDefs = array(
			'id'             => new cm_Column('SMALLINT', null, false, true, false, true, null, true),
			'event_id'       => new cm_Column('INT', null, false, false, false, true),
			'name'           => new cm_Column('VARCHAR','255', false),
			'bgImageID'      => new cm_Column('BIGINT', null, false, false, false, false),
			'customSize'     => new cm_Column('VARCHAR','255', true, defaultValue: null),
			'layoutPosition' => new cm_Column('VARCHAR','255', true, defaultValue: null),
			'layout'         => new cm_Column('TEXT',null,true),
		)
	}
}
