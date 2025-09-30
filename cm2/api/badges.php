<?php

use App\Lib\Database\cm_attendee_db;
use App\Lib\Database\cm_db;

require_once __DIR__ .'/../../src/lib/util/res.php';

$event_name = $cm_config['event']['name'];

$onsite_only = isset($_COOKIE['onsite_only']) && $_COOKIE['onsite_only'];
$override_code = $_GET['override_code'] ?? ($_POST['override_code'] ?? '');

$db = new cm_db();

$atdb = new cm_attendee_db($db);
$name_map = $atdb->list_badge_types(true, false, $onsite_only, $override_code);

echo json_encode($name_map);

//$fdb = new cm_forms_db($db, 'attendee');
//$questions = $fdb->list_questions();
