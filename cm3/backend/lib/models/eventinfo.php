<?php

namespace CM3_Lib\models;

use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\SelectColumn as cm_SelectColumn;
use CM3_Lib\database\SearchTerm as cm_SearchTerm;
use CM3_Lib\database\View as cm_View;

class eventinfo extends CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'EventInfo';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'shortcode'		=> new cm_Column('VARCHAR', 8, false, false, true, true),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'display_name'	=> new cm_Column('VARCHAR', '500', true),
            'date_start'	=> new cm_Column('DATE', null, false),
            'date_end'  	=> new cm_Column('DATE', null, false),
            'staff_start'	=> new cm_Column('DATE', null, false),
            'staff_end' 	=> new cm_Column('DATE', null, false),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true)
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('shortcode','active','display_name','date_start','date_end');
    }

    public function IsValidShortcode(string $shortcode)
    {
        $checkFound = $this->Search(
            array('id'),
            array(
                new cm_SearchTerm('shortcode', $shortcode, EncapsulationFunction: 'LCASE(?)')
            ),
            limit: 1
        );
        if ($checkFound === false) {
            return false;
        }
        return count($checkFound) > 0;
    }

    public function GetSearchTerm(string $eventIDColumnName = 'event_id', ?string $JoinedTableAlias = null): cm_SearchTerm
    {
        //Check for cookie
        if (isset($_COOKIE['cm_EventID'])) {
            //Split it and determine if valid
            $evParts = explode(':', $_COOKIE['cm_EventID']);
            if (count($evParts) == 2) {
                //Had the right parts, check the hash
                $decodedID = CM3_Lib\util\BaseIntEncoder::decode($evParts[1]);
                //Did it match?
                if ((int)$evParts[0] == $decodedID) {
                    //Looks good! Generate the search term!
                    return new cm_SearchTerm($eventIDColumnName, $decodedID, JoinedTableAlias: $JoinedTableAlias);
                }
            }
        }
        //Hrm, no cookie, eh? Well let's determine the current active/next active one and run with it.
        $thedate = date("Y/m/d");
        $result = $this->Search(
            null,
            terms: array(
            new cm_SearchTerm('date_end', $thedate, ">="),
            new cm_SearchTerm('active', true)
        ),
            order: array(
            'date_start'=> false
        ),
            limit: 1
        );

        //Did we get something?
        if ($result !== false && count($result) > 0) {
            $eventId = $result[0]['id'];
            //Tell them they have a cookie to keep hold of...
            setcookie('cm_EventID', $eventId . ':' . CM3_Lib\util\BaseIntEncoder::encode($eventId), time() + 30*60*60*24); //30 days
            //Oh, and return the cm_SearchTerm
            return new cm_SearchTerm($eventIDColumnName, $eventId, JoinedTableAlias: $JoinedTableAlias);
        } else {
            error_log("Attempted to determine current event but it seems none exist or are active?");
            //Give back a safety kill
            return new cm_SearchTerm($eventIDColumnName, null, 'is', JoinedTableAlias: $JoinedTableAlias);
        }
    }
}
