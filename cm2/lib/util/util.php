<?php

function get_domain_url() {
	$https = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on'));
	$url = ($https ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'];
	if ($_SERVER['SERVER_PORT'] != ($https ? '443' : '80')) {
		$url .= ':' . $_SERVER['SERVER_PORT'];
	}
	return $url;
}

function get_site_url($full) {
	$uriroot = realpath(__FILE__);
	$o = strpos($uriroot, '/lib/util/util.php');
	if ($o !== FALSE) $uriroot = substr($uriroot, 0, $o);
	$docroot = realpath($_SERVER['DOCUMENT_ROOT']);
	$o = strpos($uriroot, $docroot);
	if ($o !== FALSE) $uriroot = substr($uriroot, $o + strlen($docroot));
	if (!$full) return $uriroot;
	return get_domain_url() . $uriroot;
}

function get_page_url($full) {
	if (!$full) return $_SERVER['REQUEST_URI'];
	return get_domain_url() . $_SERVER['REQUEST_URI'];
}

function get_page_filename() {
	$url = $_SERVER['REQUEST_URI'];
	$o = strpos($url, '?');
	if ($o !== FALSE) $url = substr($url, 0, $o);
	$o = strrpos($url, '/');
	if ($o !== FALSE) $url = substr($url, $o + 1);
	return $url ? $url : 'index.php';
}

function ua($x) {
	return (strpos($_SERVER['HTTP_USER_AGENT'], $x) !== FALSE);
}

function paragraph_string($s) {
	$s = htmlspecialchars($s);
	$s = str_replace("\r\n", "<br>", $s);
	$s = str_replace("\r", "<br>", $s);
	$s = str_replace("\n", "<br>", $s);
	return $s;
}

function safe_html_string($s) {
	$s1 = '/&lt;a href=&quot;(([^"\'&<>]+|&amp;)*)&quot;&gt;(.*?)&lt;\\/a&gt;/';
	$r1 = '<a href="$1" target="_blank">$3</a>';
	$s2 = '/&lt;img src=&quot;(([^"\'&<>]+|&amp;)*)&quot;&gt;/';
	$r2 = '<img src="$1">';
	$s3 = '/&lt;(b|i|u|s|q|tt|em|strong|sup|sub|big|small|ins|del|abbr|cite|code|dfn|kbd|samp|var)&gt;(.*?)&lt;\\/\\1&gt;/';
	$r3 = '<$1>$2</$1>';
	$s4 = '/&lt;(br|wbr)&gt;/';
	$r4 = '<$1>';
	$s = paragraph_string($s);
	while (preg_match($s1, $s)) $s = preg_replace($s1, $r1, $s);
	while (preg_match($s2, $s)) $s = preg_replace($s2, $r2, $s);
	while (preg_match($s3, $s)) $s = preg_replace($s3, $r3, $s);
	while (preg_match($s4, $s)) $s = preg_replace($s4, $r4, $s);
	return $s;
}

function url_link($u) {
	if (!$u) return '';
	if (!preg_match('/^[A-Za-z][A-Za-z0-9.+-]*:/', $u)) $u = 'http://' . $u;
	$u = htmlspecialchars($u);
	return '<a href="' . $u . '" target="_blank">' . $u . '</a>';
}

function url_link_short($u) {
	if (!$u) return '';
	if (!preg_match('/^[A-Za-z][A-Za-z0-9.+-]*:/', $u)) $u = 'http://' . $u;
	$u = htmlspecialchars($u);
	return '<a href="' . $u . '" target="_blank">link</a>';
}

function email_link($e) {
	if (!$e) return '';
	$e = htmlspecialchars($e);
	return '<a href="mailto:' . $e . '">' . $e . '</a>';
}

function email_link_short($e) {
	if (!$e) return '';
	$e = htmlspecialchars($e);
	return '<a href="mailto:' . $e . '">link</a>';
}

function price_string($price) {
	return ($price ? ('$' . number_format($price, 2, '.', ',')) : 'FREE');
}

function parse_date($x) {
	$a = date_parse($x);
	if ($a && $a['year'] && $a['month'] && $a['day'] && !count($a['errors'])) {
		return sprintf("%04d-%02d-%02d", $a['year'], $a['month'], $a['day']);
	} else {
		return null;
	}
}

function date_range_string($start_date, $end_date) {
	if ($start_date && $end_date) {
		return htmlspecialchars($start_date) . ' &mdash; ' . htmlspecialchars($end_date);
	} else if ($start_date) {
		return 'starting ' . htmlspecialchars($start_date);
	} else if ($end_date) {
		return 'ending ' . htmlspecialchars($end_date);
	} else {
		return 'forever';
	}
}

function age_range_string($min_age, $max_age) {
	if ($min_age && $max_age) {
		return (int)$min_age . ' &mdash; ' . (int)$max_age;
	} else if ($min_age) {
		return (int)$min_age . ' and over';
	} else if ($max_age) {
		return (int)$max_age . ' and under';
	} else {
		return 'all ages';
	}
}

function cm_array_string($a) {
	if (!$a) return 'none';
	if (in_array('*', $a)) return 'all';
	return implode(', ', $a);
}

function cm_array_string_short($a) {
	if (!$a) return 'none';
	if (in_array('*', $a)) return 'all';
	if (count($a) > 1) return 'many';
	return $a[0];
}

function mail_merge($text, $fields) {
	$s = array();
	$r = array();
	foreach ($fields as $k => $v) {
		$s[] = '[[' . $k . ']]';
		$r[] = $v;
	}
	return str_replace($s, $r, $text);
}

function mail_merge_html($text, $fields) {
	$s = array();
	$r = array();
	foreach ($fields as $k => $v) {
		$s[] = '[[' . htmlspecialchars($k) . ']]';
		$r[] = htmlspecialchars($v);
	}
	return str_replace($s, $r, $text);
}