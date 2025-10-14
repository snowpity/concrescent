<?php

require_once __DIR__ .'/../../src/lib/util/res.php';

global $twig, $miscDb;

$db = $kernel->container->cm_db;
$miscDb = $kernel->container->cm_misc_db;

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

