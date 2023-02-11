<?php
require_once dirname(__FILE__) . "/../config/config.php";
require_once dirname(__FILE__) . "/../lib/database/application.php";
require_once dirname(__FILE__) . "/../lib/database/forms.php";

$context = isset($_GET["c"]) ? trim($_GET["c"]) : null;

if (!$context) {
    header("Location: ../", true, 303);
    exit;
}
$db = new cm_db();
$apdb = new cm_application_db($db, $context);
$fdb = new cm_forms_db($db, "application-".strtolower($context));
$assignments = $apdb->list_room_and_table_assignments(null, strtoupper($context));
$formQuestions = $fdb->list_questions();
$formAnswers = array();
foreach ($assignments as &$assignment)
    foreach ($formQuestions as $question)
        if ($question["exposed"])
            $assignment[$question["title"]] = $fdb->get_answer($assignment["context-id"], $question["question-id"]);
$response = ["ok" => true, "assignments" => $assignments];
header("Content-Type: application/json; charset=utf-8", true, 200);
echo json_encode($response);
