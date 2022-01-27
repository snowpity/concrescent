<?php

namespace CM3_Lib\models\mail;

use CM3_Lib\database\Column as cm_Column;

class log extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Mail_Log';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null),
            'template_id'		=> new cm_Column('INT', null, false, false, false, true),
            'success'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'meta'			=> new cm_Column('VARCHAR', 255, true),
            'data'			=> new cm_Column('TEXT', null, true),
            'result'			=> new cm_Column('VARCHAR', 255, false)
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('template_id','meta','result');
    }
}
