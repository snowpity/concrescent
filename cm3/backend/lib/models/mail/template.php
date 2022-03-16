<?php

namespace CM3_Lib\models\mail;

use CM3_Lib\database\Column as cm_Column;

class template extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Mail_Templates';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null),
            'event_id'		=> new cm_Column('INT', null, false, false, false, true),
            'name'			=> new cm_Column('VARCHAR', 255, false),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'reply_to'		=> new cm_Column('VARCHAR', 300, false),
            'from'			=> new cm_Column('VARCHAR', 300, false),
            'cc'			=> new cm_Column('VARCHAR', 2000, true),
            'bcc'			=> new cm_Column('VARCHAR', 2000, true),
            'subject'		=> new cm_Column('VARCHAR', 1000, false),
            'format'		=> new cm_Column(
                'ENUM',
                array(
                    'Text Only',
                    'Markdown',
                    'Full HTML' //Is there a reason?
                ),
                false
            ),
            'body'			=> new cm_Column('TEXT', null, true),
            //Files from the Filestore to attach if not interpreted from the body
            'attachments'   => new cm_Column('VARCHAR', 300, true),
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id');
    }
}
