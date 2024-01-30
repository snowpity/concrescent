<?php

require_once __DIR__ .'/../config/config.php';
require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/forms.php';
require_once __DIR__ .'/../lib/database/misc.php';

global $twig, $miscDb;

$db = new cm_db();
$miscDb = new cm_misc_db($db);

$websiteCreditQuestionId = $_GET['credit'] ?? null;
$staffApprovalQuestionId = $_GET['approval'] ?? null;
$sig = $_GET['sig'] ?? null;

if (!$websiteCreditQuestionId || !$staffApprovalQuestionId) {
	dieWithErrorLog('Missing parameters');
}

$expectedSignature = hash('sha256', "{$websiteCreditQuestionId}_{$staffApprovalQuestionId}_{$cm_config['secret']}");

if ($sig !== $expectedSignature) {
	dieWithErrorLog('Wrong signature.');
}

header('Content-Type: application/json');
header('Pragma: no-cache');
header('Expires: 0');
echo json_encode($miscDb->getBadgeTypesFromQuestionAnswer($websiteCreditQuestionId, $staffApprovalQuestionId), JSON_THROW_ON_ERROR);

