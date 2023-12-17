<?php

require_once __DIR__ .'/../../lib/database/badge-artwork.php';
require_once __DIR__ .'/../admin.php';

$badb = new cm_badge_artwork_db($db);

$file_name = isset($_GET['name']) ? trim($_GET['name']) : null;

if (!$badb->download_badge_artwork($file_name)) {
	header('Content-Type: image/png');
	header('Pragma: no-cache');
	header('Expires: 0');
	$image = imagecreate(300, 200);
	$bg = imagecolorallocate($image, 255, 255, 255);
	imagefilledrectangle($image, 0, 0, 300, 200, $bg);
	$fg = imagecolorallocate($image, 0, 0, 255);
	imagestring($image, 5, (300-9*21)/2, 200/2-8-12, 'Could not load image.', $fg);
	imagestring($image, 5, (300-9*24)/2, 200/2-8+12, 'Please upload a new one.', $fg);
	imagepng($image);
	imagedestroy($image);
}
