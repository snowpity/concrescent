<?php
require_once dirname(__FILE__).'/../config/config.php';
require_once dirname(__FILE__).'/../lib/database/database.php';
require_once dirname(__FILE__).'/../lib/database/attendee.php';
require_once dirname(__FILE__).'/../lib/database/forms.php';
require_once dirname(__FILE__).'/../lib/database/mail.php';
require_once dirname(__FILE__).'/../lib/util/res.php';
require_once dirname(__FILE__).'/../lib/util/util.php';

$event_name = $cm_config['event']['name'];

$onsite_only = isset($_COOKIE['onsite_only']) && $_COOKIE['onsite_only'];
$override_code = isset($_GET['override_code']) ? $_GET['override_code'] : (isset($_POST['override_code']) ? $_POST['override_code'] :'') ;

$db = new cm_db();

$atdb = new cm_attendee_db($db);
$badge_type_name_map = $atdb->get_badge_type_name_map();
$active_addons = $atdb->list_addons(true, false, $onsite_only, $badge_type_name_map);

echo json_encode($active_addons);

//$fdb = new cm_forms_db($db, 'attendee');
//$questions = $fdb->list_questions();
