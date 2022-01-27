<?php

namespace CM3_Lib\models\forms;

use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\ColumnIndex as cm_ColumnIndex;

class questionmap extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Forms_Questions_Maps';
        $this->ColumnDefs = array(
            'context'		=> new cm_Column('VARCHAR', 3, false),
            'context_id'	=> new cm_Column('INT', null, false),
            'question_id'	=> new cm_Column('INT', null, false),
            'required'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false')
        );
        $this->IndexDefs = array(
            'primary' => new cm_ColumnIndex(array(
                'context' => false,
                'context_id' => false,
                'question_id' => false,
            ), 'primary key')
        );
        $this->PrimaryKeys = array();
        $this->DefaultSearchColumns = array('question_id','required');
    }
}
