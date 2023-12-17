<?php

require_once __DIR__ .'/../config/config.php';
require_once __DIR__ .'/../lib/database/attendee.php';
require_once __DIR__ .'/../lib/database/application.php';
require_once __DIR__ .'/../lib/database/staff.php';
require_once __DIR__ .'/admin.php';

cm_admin_check_permission('statistics', 'statistics');

function atsection($db) {
	$atdb = new cm_attendee_db($db);
	$atnames = $atdb->get_badge_type_name_map();
	$atstat = $atdb->get_attendee_statistics(300, $atnames);
	$atnames['*'] = 'All Attendees';
	return array('names' => $atnames, 'stat' => $atstat);
}

function apsection($db, $context, $ctx_info) {
	$apdb = new cm_application_db($db, $context);
	$apnames = $apdb->get_badge_type_name_map();
	$apstat = $apdb->get_applicant_statistics(300, $apnames);
	$apnames['*'] = 'All ' . $ctx_info['nav_prefix'] . ' Badges';
	return array('names' => $apnames, 'stat' => $apstat);
}

function ssection($db) {
	$sdb = new cm_staff_db($db);
	$snames = $sdb->get_badge_type_name_map();
	$sstat = $sdb->get_staff_statistics(300, $snames);
	$snames['*'] = 'All Staff';
	return array('names' => $snames, 'stat' => $sstat);
}

$sections = array();
$sections[] = atsection($db);
foreach ($cm_config['application_types'] as $context => $ctx_info) {
	$sections[] = apsection($db, $context, $ctx_info);
}
$sections[] = ssection($db);

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

$columns = array('In System', 'Paid', 'Printed', 'Checked In');

$timestamps = array();
$header = array('Date');
$last = array(0);
foreach ($sections as $section) {
	$timestamps += $section['stat']['timestamps'];
	foreach ($section['names'] as $name) {
		foreach ($columns as $col) {
			$header[] = $name . ' - ' . $col;
			$last[] = 0;
		}
	}
}
foreach ($columns as $col) {
	$header[] = 'Grand Total - ' . $col;
	$last[] = 0;
}
ksort($timestamps);

$data = array($header);
foreach ($timestamps as $timestamp) {
	$row = array($timestamp);
	$totals = array();
	foreach ($columns as $idx => $col) {
		$totals[$idx] = 0;
	}
	foreach ($sections as $section) {
		$timelines = $section['stat']['timelines'];
		foreach ($section['names'] as $id => $name) {
			foreach ($columns as $idx => $col) {
				$index = count($row);
				if (isset($timelines[$id][$idx][$timestamp])) {
					$last[$index] = $timelines[$id][$idx][$timestamp];
				}
				if ($id == '*') {
					$totals[$idx] += $last[$index];
				}
				$row[] = $last[$index];
			}
		}
	}
	foreach ($columns as $idx => $col) {
		$row[] = $totals[$idx];
	}
	$data[] = $row;
}

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

cm_admin_head('Statistics');
if (count($header) > 1 && count($data) > 1) {
	$json_data = json_encode($data);
	$json_data = preg_replace('/\\[([0-9]+)/', '[new Date($1)', $json_data);
	echo '<script type="text/javascript">';
	echo 'cm_stat_data = (' . $json_data . '); ';
	echo 'cm_stat_count = ' . count($header) . ';';
	echo '</script>';
	echo '<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>';
	echo '<script type="text/javascript" src="statistics.js"></script>';
}

cm_admin_body('Statistics');
cm_admin_nav('statistics');

echo '<article>';
	echo '<div class="card">';
		echo '<div class="card-content">';
			if (count($header) > 1 && count($data) > 1) {
				echo '<div class="spacing"><div id="cm-stat-chart">Loading Chart...</div></div>';
			}
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-statistics">';
				echo '<tr>';
					echo '<th class="cm-stat-corner">Badge Type</th>';
					foreach ($columns as $idx => $col) {
						echo '<th class="td-numeric cm-stat-col" id="cm-stat-col-'.($idx+1).'">';
						echo htmlspecialchars($col);
						echo '</th>';
					}
				echo '</tr>';
				$index = 0;
				$totals = array();
				foreach ($columns as $idx => $col) {
					$totals[$idx] = 0;
				}
				foreach ($sections as $section) {
					$counters = $section['stat']['counters'];
					foreach ($section['names'] as $id => $name) {
						echo '<tr>';
							$td = ($id == '*') ? 'th' : 'td';
							echo '<'.$td.' class="cm-stat-row" id="cm-stat-row-'.($index+1).'">';
							echo htmlspecialchars($name);
							echo '</'.$td.'>';
							foreach ($columns as $idx => $col) {
								echo '<'.$td.' class="td-numeric cm-stat" id="cm-stat-'.(++$index).'">';
								echo number_format($counters[$id][$idx]);
								echo '</'.$td.'>';
								if ($id == '*') {
									$totals[$idx] += $counters[$id][$idx];
								}
							}
						echo '</tr>';
					}
				}
				echo '<tr>';
					echo '<th class="cm-stat-row" id="cm-stat-row-'.($index+1).'">Grand Total</th>';
					foreach ($columns as $idx => $col) {
						echo '<th class="td-numeric cm-stat" id="cm-stat-'.(++$index).'">';
						echo number_format($totals[$idx]);
						echo '</th>';
					}
				echo '</tr>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
