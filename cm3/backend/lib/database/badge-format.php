<?php

require_once dirname(__FILE__).'/database.php';
require_once dirname(__FILE__).'/eventinfo.php';
require_once dirname(__FILE__).'/filestore.php';

class cm_badge_format_db extends cm_Table {
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

class cm_badge_format_map_db extends cm_Table{
	protected function setupTableDefinitions() : void
	{
		$this->TableName  = 'Badge_Format_Map';
		$this->ColumnDefs = array(
			'badge_id'			=> new cm_Column('INT',null, false),
			'format_id' 		=> new cm_Column('SMALLINT', null, false),
		)
	}
}
