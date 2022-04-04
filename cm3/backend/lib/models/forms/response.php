<?php

namespace CM3_Lib\models\forms;

use CM3_Lib\database\Column;
use CM3_Lib\database\ColumnIndex;

class response extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Forms_Responses';
        $this->ColumnDefs = array(
            'question_id'	=> new Column('INT', null, false, false, false, false, null, false),
            'context_code'		=> new Column('VARCHAR', 3, false),
            'context_id'	=> new Column('BIGINT', null, false),
            'response'		=> new Column('TEXT', null, true)
        );
        $this->IndexDefs = array(
            'primary' => new ColumnIndex(array(
                'question_id' => false,
                'context_code' => false,
                'context_id' => false
            ), 'primary key')
        );
        $this->PrimaryKeys = array('question_id'=>false,
        'context_code' => false,
        'context_id' => false);
        $this->DefaultSearchColumns = array('question_id', 'context_code','context_id');
    }
}
