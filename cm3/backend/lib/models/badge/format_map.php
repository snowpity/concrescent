<?php

namespace CM3_Lib\models\badge;

use \CM3_Lib\database\Column as cm_Column;

class format_map extends CM3_Lib\database\Table{
	protected function setupTableDefinitions() : void
	{
		$this->TableName  = 'Badge_Format_Map';
		$this->ColumnDefs = array(
			'badge_id'			=> new cm_Column('INT',null, false),
			'format_id' 		=> new cm_Column('SMALLINT', null, false),
		)
	}
}
