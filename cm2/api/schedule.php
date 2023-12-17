<?php

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        // may also be using PUT, PATCH, HEAD etc
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
}

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../lib/database/application.php";
require_once __DIR__ . "/../lib/database/forms.php";

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
