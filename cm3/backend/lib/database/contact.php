<?php

require_once dirname(__FILE__).'/database.php';

class cm_contact_db extends cm_Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Contacts';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null, true),
            'uuid_raw'		=> new cm_Column('BINARY', 16, false, false, true, false, '(UUID_TO_BIN(UUID()))'),
            'uuid'			=> new cm_Column('CHAR', 36, null, false, false, false, null, false, 'GENERATED ALWAYS as (BIN_TO_UUID(`uuid_raw`)) VIRTUAL'),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'allow_marketing'=> new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'email_address' => new cm_Column('VARCHAR', '255', false, false, true, true),
            'pass'			=> new cm_Column('VARCHAR', '255', true, false, false, true),
            'real_name'		=> new cm_Column('VARCHAR', '500', true),
            'phone_number'	=> new cm_Column('VARCHAR', '255', true),
            'address_1'		=> new cm_Column('VARCHAR', '255', true),
            'address_2'		=> new cm_Column('VARCHAR', '255', true),
            'city'			=> new cm_Column('VARCHAR', '255', true),
            'state'			=> new cm_Column('VARCHAR', '255', true),
            'zip_code'		=> new cm_Column('VARCHAR', '255', true),
            'country'		=> new cm_Column('VARCHAR', '255', true),
            'notes'			=> new cm_Column('TEXT', null, true)
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','real_name','email_address','allow_marketing');
        $this->Views = array(
            'main' => new cm_View(
                array(
                new cm_SelectColumn('id'),
                new cm_SelectColumn('allow_marketing'),
                new cm_SelectColumn('email_address'),
                new cm_SelectColumn('real_name'),
                new cm_SelectColumn('phone_number'),
            )
            ));
    }

    public function unsubscribe_email_address($email): bool
    {
        if (!$email) {
            return false;
        }
        //Find them
        $contacts = $this->Search(
            array('id','allow_marketing'),
            array(new cm_SearchTerm('email_address', $email, EncapsulationFunction: 'LCASE(?)')),
            limit: 1
        );
        //Did we find it, and are they still subscribed?
        if (count($contacts) && $contacts[0]['allow_marketing'] > 0) {
            $result = $this->Update(array(
                'id' => $contacts[0]['id'],
                'allow_marketing' => false
            ));
            if ($result !== false) {
                return true;
            }
        }
        return false;
    }
}
