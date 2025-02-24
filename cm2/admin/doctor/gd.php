<?php

require_once 'util.php';
error_reporting(0);

$success = false;

function print_success() {
	if ($GLOBALS['success']) {
        passed('gd', 'The GD library is installed and working.');
	} else {
        failed('gd', 'The GD library is not installed or is not working. Please reinstall the GD library.');
	}
}

register_shutdown_function(print_success(...));

$image = @imagecreate(200, 200);
if (!$image) exit(0);

$bg = @imagecolorallocate($image, 255, 255, 255);
if ($bg === false) exit(0);

$drew = @imagefilledrectangle($image, 0, 0, 200, 200, $bg);
if (!$drew) exit(0);

$fg = @imagecolorallocate($image, 0, 0, 255);
if ($fg === false) exit(0);

$drew = @imagestring($image, 5, 100, 100, 'CM', $fg);
if (!$drew) exit(0);

ob_start();
$pung = @imagepng($image);
$png = ob_get_contents();
ob_end_clean();
if (!$pung || !$png) exit(0);

$destroyed = @imagedestroy($image);
if (!$destroyed) exit(0);

$success = true;
