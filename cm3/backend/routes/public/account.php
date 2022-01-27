<?php

//Early exit until this actually gets written
return function () {
};
require_once dirname(__FILE__).'/../lib/database/contact.php';
require_once dirname(__FILE__).'/../lib/database/attendee.php';

session_name('PHPSESSID_CMREG');
session_start();
class bergs_db extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'bergs';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'berg'			=> new cm_Column('INT', null, false)
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','berg');
        $this->Views = array();
    }
}

$db = new cm_db();
$contacts = new cm_contact_db($db);
$bergs = new bergs_db($db);

// $newID = $contacts->Create(array(
//     'email_address' => rand(1000,9000) . '@junk.com'
// ));

// $newID2 = $contacts->Update(array(
//     'id'=>6,
//     'display_name'=> rand(1000,9000) . ' of ' . rand(1000,9000)
// ));

//echo 'ID ' . print_r($newID, true) . ' and then ' . print_r($newID2, true)``;

//echo $contacts->Delete(array('id'=>$newID['id']));
//var_dump($bergs);

// $results = $contacts->Search(
//     new cm_View(
//        array(
//            new cm_SelectColumn('id'),
//            new cm_SelectColumn('allow_marketing'),
//            new cm_SelectColumn('email_address'),
//            new cm_SelectColumn('display_name'),
//            new cm_SelectColumn('phone_number'),
//            new cm_SelectColumn('berg', JoinedTableAlias: 'b')
//        ),
//        array(
//            new cm_Join($bergs,
//                array('id'=>'id'),
//                'LEFT',
//                alias: 'b',
//                subQSelectColumns: array(
//                    new cm_SelectColumn('id'),
//                    new cm_SelectColumn('berg', EncapsulationFunction: '? * 3', Alias: 'berg')
//                )
//            )
//        )
//     ),
//     array(
//         new cm_SearchTerm('id', 1, '>')
//     ),
//     array(
//         'berg'
//     )
//     , 10, 0
// );
//
// print_r($results);

$attendees = new cm_attendee_db($db);
$results = $attendees->Search('default');
print_r($results);
