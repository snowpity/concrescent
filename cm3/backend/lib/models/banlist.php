<?php

namespace CM3_Lib\models;

use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\SearchTerm as cm_SearchTerm;

class banlist extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName                  = 'Banlist';
        $this->ColumnDefs                 = array(
            'id'                       => new cm_Column('INT', null, false, true, false, true, null, true),
            'real_name'                => new cm_Column('VARCHAR', '500', false),
            'fandom_name'              => new cm_Column('VARCHAR', '255', true),
            'email_address'            => new cm_Column('VARCHAR', '255', true),
            'phone_number'          => new cm_Column('VARCHAR', '255', true),
            'added_by'                 => new cm_Column('VARCHAR', '255', true),
            'context'                  => new cm_Column('TEXT', null, true),

            'date_created'             => new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'         => new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'date_expired'          => new cm_Column('TIMESTAMP', null, true, false, false, false),
            'notes'                    => new cm_Column('TEXT', null, true),

            //Normalized computed columns
            'normalized_real_name'  => new cm_Column('VARCHAR', '500', null, customPostfix: 'GENERATED ALWAYS as (UPPER(REGEXP_REPLACE(`real_name`,\'[^A-Za-z0-9]+\',\'\'))) STORED'),
            'normalized_fandom_name'   => new cm_Column('VARCHAR', '255', null, customPostfix: 'GENERATED ALWAYS as (UPPER(REGEXP_REPLACE(`fandom_name`,\'[^A-Za-z0-9]+\',\'\'))) STORED'),
            'normalized_email_address' => new cm_Column('VARCHAR', '255', null, customPostfix: 'GENERATED ALWAYS as (UPPER(REGEXP_REPLACE(`email_address`,\'[^A-Za-z0-9]+\',\'\'))) STORED'),
            'normalized_phone_number'  => new cm_Column('VARCHAR', '255', null, customPostfix: 'GENERATED ALWAYS as (UPPER(REGEXP_REPLACE(`phone_number`,\'[^A-Za-z0-9]+\',\'\'))) STORED'),

        );
        $this->IndexDefs                  = array();
        $this->PrimaryKeys                = array('id'=>false);
        $this->DefaultSearchColumns = array('id','real_name','email_address','phone_number');
    }

    public function is_banlisted($entity)
    {
        if (!$entity) {
            return false;
        }

        $whereTerms                       = array();
        //Translation map
        foreach (array(
            'real_name' => 'normalized_real_name',
            'fandom_name' => 'normalized_real_name',
            'ice_name' => 'normalized_real_name',
            'group_name' => 'normalized_real_name',

            'real_name' => 'normalized_fandom_name',
            'fandom_name' => 'normalized_fandom_name',
            'ice_name' => 'normalized_fandom_name',
            'group_name' => 'normalized_fandom_name',

            'notify_email' => 'normalized_email_address',
            'ice_email_address' => 'normalized_email_address',
            'ice_phone_number' => 'normalized_phone_number',
            'phone_number' => 'normalized_phone_number',
            'real_name' => 'real_name',
        ) as $entityName => $banlistName) {
            if (isset($entity[$entityName]) && $entity[$entityName] != null) {
                $whereTerms[]                   = $this->getWhereTerm($entity[$entityName], $banlistName);
            }
        }

        //If this entity had no fields that identify bans,
        if (count($whereTerms) == 0) {
            // then obviously we can't say they're banned.
            return false;
        }

        $result                           = $this->Search(array('id'), array(
                new cm_SearchTerm('date_expired', null, 'IS NULL'),
                new cm_SearchTerm(null, null, subSearch: $whereTerms)
            ), limit: 1);

        return $result && count($result) > 0;
    }

    public function getWhereTerm($paramValue, $matchOn)
    {
        $normalizedValue                  = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $paramValue));
        return new cm_SearchTerm($matchOn, $normalizedValue, TermType: 'OR');
    }
}
