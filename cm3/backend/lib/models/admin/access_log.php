<?php

namespace CM3_Lib\models\admin;

use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\ColumnIndex as cm_ColumnIndex;

class access_log extends CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Admin_Access_Log';
        $this->ColumnDefs = array(
            'id'		 	=> new cm_Column('BIGINT', null, false, true, false, true, null, true),
            'timestamp'		=> new cm_Column('TIMESTAMP', null, false, defaultValue:'CURRENT_TIMESTAMP'),
            'contact_id' 	=> new cm_Column('BIGINT', null, false),
            'remote_addr'	=> new cm_Column('VARCHAR', 255, false),
            'remote_host'	=> new cm_Column('VARCHAR', 255, false),
            'request_uri'	=> new cm_Column('VARCHAR', 255, false),
            'http_referrer'	=> new cm_Column('VARCHAR', 255, false),
            'http_user_agent'	=> new cm_Column('VARCHAR', 255, false),
            'module'	=> new cm_Column('VARCHAR', 255, false),
            'action'	=> new cm_Column('VARCHAR', 255, false),
            'postdata'	=> new cm_Column('TEXT', null, true),

        );
        $this->IndexDefs = array(
            'primary' => new cm_ColumnIndex(array(
                'contact_id' => false,
                'remote_host' => false
            )));
        $this->PrimaryKeys = array('contact_id'=>false);
        $this->DefaultSearchColumns = array('id');
    }


    public function log_access()
    {
        //TODO: Add ability to specify module info, params, etc
        return $this->Create(
            array(
                'contact_id' => getContactId(),
                'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
                'remote_host' => $_SERVER['REMOTE_HOST'] ?? '',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'http_referrer' => $_SERVER['HTTP_REFERER'] ?? '',
                'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',

            )
        );
    }
}
