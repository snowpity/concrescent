<?php

namespace CM3_Lib\models\admin;

use CM3_Lib\database\Column as cm_Column;

class users extends CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Admin_Users';
        $this->ColumnDefs = array(
            'contact_id' 	=> new cm_Column('BIGINT', null, false, true, false, true, null),
            'username'		=> new cm_Column('VARCHAR', 255, true, false, true),
            'password'		=> new cm_Column('VARCHAR', 255, true, false, true),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'adminOnly'     => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'preferences'	=> new cm_Column('TEXT', null, true),
            'permissions'	=> new cm_Column('TEXT', null, true)
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('contact_id'=>false);
        $this->DefaultSearchColumns = array('id');
    }
}
