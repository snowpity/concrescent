<?php

namespace CM3_Lib\models\forms;

use CM3_Lib\database\Column as cm_Column;

class response extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Forms_Responses';
        $this->ColumnDefs = array(
            'question_id'	=> new cm_Column('INT', null, false, false, false, false, null, false),
            'context'		=> new cm_Column('VARCHAR', 3, false),
            'context_id'	=> new cm_Column('BIGINT'),
            'response'		=> new cm_Column('TEXT', null, true)
        );
        $this->IndexDefs = array(
            'primary' => new cm_ColumnIndex(array(
                'question_id' => false,
                'context' => false,
                'context_id' => false
            ), 'primary key')
        );
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('question_id');
    }
}
