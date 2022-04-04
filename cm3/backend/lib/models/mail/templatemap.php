<?php

namespace CM3_Lib\models\mail;

use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\ColumnIndex as cm_ColumnIndex;

class templatemap extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Mail_Template_Maps';
        $this->ColumnDefs = array(
            'context_code'		=> new cm_Column('VARCHAR', 3, false),
            'badge_type_id'	=> new cm_Column('INT', null, false),
            'template_id'	=> new cm_Column('INT', null, false),
            'reason'        => new cm_Column('varchar', 20, false)
        );
        $this->IndexDefs = array(
            'primary' => new cm_ColumnIndex(array(
                'context_code' => false,
                'badge_type_id' => false,
                'template_id' => false,
            ), 'primary key')
        );
        $this->PrimaryKeys = array(
            'context_code' => false,
            'badge_type_id' => false,
            'template_id' => false);
        $this->DefaultSearchColumns = array('template_id','reason');
    }
}
