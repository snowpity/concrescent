<?php

require_once __DIR__ .'/../../lib/util/res.php';
error_reporting(0);
header('Content-Type: text/plain');

$css = theme_file_path('theme.css');
if ($css && file_exists($css)) {
	echo 'OK Theme directory and stylesheet exist.';
} else {
	echo 'NG Theme directory and/or stylesheet does not exist. Check theme configuration.';
}
