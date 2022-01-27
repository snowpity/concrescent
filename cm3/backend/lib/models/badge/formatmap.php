<?php

namespace CM3_Lib\models\badge;

use \CM3_Lib\database\Column as cm_Column;

class formatmap extends \CM3_Lib\database\Table{
	protected function setupTableDefinitions() : void
	{
		$this->TableName  = 'Badge_Format_Maps';
		$this->ColumnDefs = array(
			'format_id' 		=> new cm_Column('SMALLINT', null, false),
			'badge_type_id'	=> new cm_Column('INT',null, false),
			'category'   => new cm_Column('ENUM', array('Attendee','Assignment','Staff'), false, defaultValue: 'Assignment'),
		)
	}
}
