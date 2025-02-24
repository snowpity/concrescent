<?php

require_once 'util.php';
require_once __DIR__ .'/../../lib/util/res.php';
error_reporting(0);

$css = theme_file_path('theme.css');
if ($css && file_exists($css)) {
    passed('theme', 'Theme directory and stylesheet exist.');
} else {
    notice('theme', 'Theme directory and/or stylesheet does not exist. Check theme configuration.');
}
