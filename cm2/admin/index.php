<?php

require_once __DIR__ .'/admin.php';
global $twig;

$now = new \DateTimeImmutable('now');
$nowFormatted = $now->format('r, e \[Y-m-d\TH:i:sP]');

echo $twig->render('pages/admin/index.twig', [
	'nowFormatted' => $nowFormatted,
]);
