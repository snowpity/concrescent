<?php

namespace CM3_Lib\models\forms;

use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\ColumnIndex as cm_ColumnIndex;

class questionmap extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Mail_Template_Maps';
        $this->ColumnDefs = array(
            'context'		=> new cm_Column('VARCHAR', 3, false),
            'badge_type_id'	=> new cm_Column('INT', null, false),
            'template_id'	=> new cm_Column('INT', null, false),
            'reason'        => new cm_Column('varchar', 20, false)
        );
        $this->IndexDefs = array(
            'primary' => new cm_ColumnIndex(array(
                'context' => false,
                'badge_type_id' => false,
                'question_id' => false,
            ), 'primary key')
        );
        $this->PrimaryKeys = array(
            'context' => false,
            'badge_type_id' => false,
            'template_id' => false);
        $this->DefaultSearchColumns = array('template_id','reason');
    }
}
