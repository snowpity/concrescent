<?php

use JetBrains\PhpStorm\NoReturn;

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../../lib/database/badge-artwork.php';
require_once __DIR__ .'/../../lib/database/badge-holder.php';
require_once __DIR__ .'/../../lib/util/res.php';
require_once __DIR__ .'/../admin.php';

function get_config($p, $g, $c, $k) {
	if ($p && isset($_POST[$p])) return $_POST[$p];
	if ($g && isset($_GET[$g])) return $_GET[$g];
	if ($c && isset($_COOKIE[$c])) return $_COOKIE[$c];
	if ($k) return $GLOBALS['cm_config']['badge_printing'][$k];
	return false;
}

#[NoReturn]
function print_error($h, $p) {
	echo '<!DOCTYPE HTML>';
	echo '<html>';
		echo '<head>';
			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
			echo '<title>Error</title>';
			echo '<style>';
				echo '*{margin:0;padding:0;}';
				echo 'h3,p{margin:20px;}';
			echo '</style>';
		echo '</head>';
		echo '<body>';
			if ($h) echo '<h3>' . htmlspecialchars($h) . '</h3>';
			if ($p) echo '<p>' . htmlspecialchars($p) . '</p>';
		echo '</body>';
	echo '</html>';
	exit(0);
}

$width = get_config('width', 'w', 'badge_printing_width', 'width');
$height = get_config('height', 'h', 'badge_printing_height', 'height');
$vertical = !!get_config('vertical', 'v', 'badge_printing_vertical', 'vertical');
$blank = !!get_config('blank', 'b', 'badge_printing_blank', false);
$only_print = get_config('only-print', 'o', 'badge_printing_only_print', false);
$only_print = $only_print ? explode("\n", $only_print) : false;
$post_url = get_config('post-url', 'u', 'badge_printing_post_url', 'post_url');
$post_url = $post_url ? $post_url : false;

$bp_config = $cm_config['badge_printing'];
$badb = new cm_badge_artwork_db($db);
$bhdb = new cm_badge_holder_db($db);

if (isset($_POST['artwork'])) $artwork_name = $_POST['artwork'];
else if (isset($_GET['a'])) $artwork_name = $_GET['a'];
else print_error('Badge artwork not found.', null);

$artwork = $badb->get_badge_artwork($artwork_name, $post_url ? 'base64' : false);
if (!$artwork) print_error('Badge artwork not found.', null);

if ($only_print && !in_array($artwork_name, $only_print)) {
	print_error(
		'This computer cannot be used to print this type of badge.',
		'Please instruct the badge holder to move to another line.'
	);
}

if (isset($_POST['entity'])) {
	$entity = json_decode($_POST['entity'], true);
} else if (isset($_POST['context']) && isset($_POST['context-id'])) {
	$bhdb->badge_holder_printed($_POST['context'], $_POST['context-id']);
	$entity = $bhdb->get_badge_holder($_POST['context'], $_POST['context-id']);
} else if (isset($_GET['e'])) {
	$entity = json_decode($_GET['e'], true);
} else if (isset($_GET['c']) && isset($_GET['i'])) {
	$bhdb->badge_holder_printed($_GET['c'], $_GET['i']);
	$entity = $bhdb->get_badge_holder($_GET['c'], $_GET['i']);
} else {
	$entity = null;
}
if (!$entity) print_error('Badge holder not found.', null);

echo '<!DOCTYPE HTML>';
echo '<html>';
	echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
		echo '<title>';
			echo 'CONcrescent Badge';
			if (isset($entity['id-string'])) echo ' ' . htmlspecialchars($entity['id-string']);
			if (isset($entity['display-name'])) echo ' - ' . htmlspecialchars($entity['display-name']);
		echo '</title>';

		echo '<style>';
			echo '*{margin:0;padding:0;}';
			echo '.badge{';
				echo 'position:relative;';
				if (!$artwork['vertical'] == !$vertical) {
					echo 'width:' . htmlspecialchars($width) . ';';
					echo 'height:' . htmlspecialchars($height) . ';';
				} else {
					echo 'width:' . htmlspecialchars($height) . ';';
					echo 'height:' . htmlspecialchars($width) . ';';
					echo '-webkit-transform-origin:0 0 0;';
					echo '-webkit-transform:translateY(' . $height . ') rotate(-90deg);';
					echo '-moz-transform-origin:0 0 0;';
					echo '-moz-transform:translateY(' . $height . ') rotate(-90deg);';
					echo '-ms-transform-origin:0 0 0;';
					echo '-ms-transform:translateY(' . $height . ') rotate(-90deg);';
					echo 'transform-origin:0 0 0;';
					echo 'transform:translateY(' . $height . ') rotate(-90deg);';
				}
				if (!$blank) {
					echo 'background:url(\'artwork-image.php?name=' . urlencode($artwork_name) . '\');';
					echo 'background-repeat:no-repeat;';
					echo 'background-position:center;';
					echo 'background-size:100% 100%;';
				}
			echo '}';
			echo '.field{';
				echo 'position:absolute;';
				echo 'text-align:center;';
				echo 'display:-webkit-flex;';
				echo '-webkit-align-content:center;';
				echo '-webkit-align-items:center;';
				echo '-webkit-justify-content:center;';
				echo 'display:flex;';
				echo 'align-content:center;';
				echo 'align-items:center;';
				echo 'justify-content:center;';
			echo '}';
		echo '</style>';

		if (isset($bp_config['stylesheet']) && $bp_config['stylesheet']) {
			foreach ($bp_config['stylesheet'] as $stylesheet) {
				echo '<link rel="stylesheet" href="' . htmlspecialchars($stylesheet) . '">';
			}
		}

		echo '<script type="text/javascript" src="' . htmlspecialchars(resource_file_url('jquery.js', false)) . '"></script>';
		echo '<script type="text/javascript" src="' . htmlspecialchars(resource_file_url('cmui.js', false)) . '"></script>';

		echo '<script type="text/javascript">';
			echo 'cm_print_global_config = (' . json_encode($bp_config) . ');';
			echo 'cm_print_local_config = (' . json_encode(array(
				'width' => $width, 'height' => $height, 'vertical' => $vertical,
				'blank' => $blank, 'only-print' => $only_print, 'post-url' => $post_url
			)) . ');';
			echo 'cm_print_artwork = (' . json_encode($artwork) . ');';
			echo 'cm_print_entity = (' . json_encode($entity) . ');';
		echo '</script>';

		echo '<script type="text/javascript" src="print.js"></script>';

	echo '</head>';
	echo '<body>';
		echo '<div class="badge">';

			foreach ($artwork['fields'] as $i => $field) {
				$key = $field['field-key'];
				if (substr($key, 0, 8) == 'img-src=') {
					$key = substr($key, 8);
					if (isset($entity[$key])) {
						$content = trim($entity[$key]);
						if ($content) {
							echo '<div class="field" id="field-' . $i . '" style="';
								echo 'top:' . (min($field['y1'], $field['y2']) * 100) . '%;';
								echo 'left:' . (min($field['x1'], $field['x2']) * 100) . '%;';
								echo 'right:' . ((1 - max($field['x1'], $field['x2'])) * 100) . '%;';
								echo 'bottom:' . ((1 - max($field['y1'], $field['y2'])) * 100) . '%;';
								echo 'background:url(\'' . htmlspecialchars($content) . '\');';
								echo 'background-repeat:no-repeat;';
								echo 'background-position:center;';
								echo 'background-size:contain;';
							echo '"></div>';
						}
					}
				} else {
					if (isset($entity[$key])) {
						$content = trim($entity[$key]);
						if ($content) {
							echo '<div class="field" id="field-' . $i . '" style="';
								echo 'top:' . (min($field['y1'], $field['y2']) * 100) . '%;';
								echo 'left:' . (min($field['x1'], $field['x2']) * 100) . '%;';
								echo 'right:' . ((1 - max($field['x1'], $field['x2'])) * 100) . '%;';
								echo 'bottom:' . ((1 - max($field['y1'], $field['y2'])) * 100) . '%;';
								echo 'font-size:' . $field['font-size'] . 'px;';
								echo 'font-family:' . htmlspecialchars($field['font-family']) . ';';
								echo 'font-weight:' . ($field['font-weight-bold'] ? 'bold' : 'normal') . ';';
								echo 'font-style:' . ($field['font-style-italic'] ? 'italic' : 'normal') . ';';
								if (isset($entity['age']) && $entity['age'] < 18) {
									echo 'background:' . htmlspecialchars($field['background-minors']) . ';';
									echo 'color:' . htmlspecialchars($field['color-minors']) . ';';
								} else {
									echo 'background:' . htmlspecialchars($field['background']) . ';';
									echo 'color:' . htmlspecialchars($field['color']) . ';';
								}
							echo '">';
								echo htmlspecialchars($content);
							echo '</div>';
						}
					}
				}
			}

		echo '</div>';
	echo '</body>';
echo '</html>';
