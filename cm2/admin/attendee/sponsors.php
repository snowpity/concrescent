<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../admin.php';

global $twig, $fdb;

cm_admin_check_permission('attendee-csv', 'attendee-csv');

$fdb = new cm_forms_db($db, 'attendee');
$questions = $fdb->list_questions();

$websiteCreditQuestionId = $_GET['credit'] ?? null;
$staffApprovalQuestionId = $_GET['approval'] ?? null;

if ($websiteCreditQuestionId && $staffApprovalQuestionId) {
	$secret = $cm_config['secret'] ?? '';
	$signature = hash('sha256', "{$websiteCreditQuestionId}_{$staffApprovalQuestionId}_$secret");

	$signedUrl = get_site_url();
	$signedUrl .= '/api/sponsors.php';
	$signedUrl .= "?credit=$websiteCreditQuestionId&approval=$staffApprovalQuestionId&sig=$signature";
}

echo $twig->render('pages/admin/attendee/sponsors.twig', [
	'questions' => $questions,
	'websiteCreditQuestionId' => $websiteCreditQuestionId,
	'staffApprovalQuestionId' => $staffApprovalQuestionId,
	'signedUrl' => $signedUrl ?? null,
]);
