<?php

require_once __DIR__ .'/../../lib/database/staff.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('staff-orgchart', 'staff-orgchart');
$can_edit = $adb->user_has_permission($admin_user, 'staff-edit');
$can_view = $adb->user_has_permission($admin_user, 'staff-view');
$can_review = $adb->user_has_permission($admin_user, 'staff-review');
$has_actions = ($can_edit || $can_view || $can_review);
$colspan = ($has_actions ? 7 : 6);

$sdb = new cm_staff_db($db);
$departments = $sdb->list_departments();

$staff = $sdb->list_staff_members();
$staff = array_filter($staff, function($member) {
	return (
		$member['application-status'] == 'Accepted'
	);
});
usort($staff, function($a, $b) {
	return strnatcasecmp($a['real-name'], $b['real-name']);
});

function echo_staff_member($level, $position_name, $other, $member) {
	global $can_edit, $can_view, $can_review, $has_actions;
	echo '<tr class="cm-orgchart-staff-level-'.(int)$level.'">';
		echo '<td>';
			if ($other) echo '<i>';
			echo htmlspecialchars($position_name);
			if ($other) echo '</i>';
		echo '</td>';
		echo '<td>' . htmlspecialchars($member['real-name']) . '</td>';
		echo '<td>' . htmlspecialchars($member['fandom-name']) . '</td>';
		echo '<td>' . cm_email_subbed($member['subscribed'], $member['email-address']) . '</td>';
		echo '<td>' . email_link($member['mail-alias-1']) . '</td>';
		echo '<td>' . htmlspecialchars($member['phone-number']) . '</td>';
		if ($has_actions) {
			echo '<td class="td-actions">';
				if ($can_edit || $can_view) {
					echo '<a href="edit.php?id='.$member['id'].'" target="_blank" class="button">';
					echo $can_edit ? 'Edit' : 'View';
					echo '</a>';
				}
				if ($can_review) {
					echo '<a href="edit.php?review&id='.$member['id'].'" target="_blank" class="button">';
					echo 'Review';
					echo '</a>';
				}
			echo '</td>';
		}
	echo '</tr>';
}

function echo_position($level, $position) {
	global $staff;
	foreach ($staff as $member) {
		if (in_array($position['id'], $member['assigned-position-ids'])) {
			echo_staff_member($level, $position['name'], false, $member);
		}
	}
}

function echo_other_positions($level, $dept) {
	global $staff;
	foreach ($staff as $member) {
		foreach ($member['assigned-positions'] as $ap) {
			if ($ap['department-id'] == $dept['id'] && !$ap['position-id']) {
				echo_staff_member($level, $ap['position-name'], true, $member);
			}
		}
	}
}

function echo_department($level, $dept) {
	global $colspan, $departments;
	echo '<tr class="cm-orgchart-dept-level-'.(int)$level.'">';
		echo '<td colspan="'.$colspan.'">';
			switch ($level) {
				case 0: echo '<h2>' . htmlspecialchars($dept['name']) . '</h2>'; break;
				case 1: echo '<h3>' . htmlspecialchars($dept['name']) . '</h3>'; break;
				default: echo '<b>' . htmlspecialchars($dept['name']) . '</b>'; break;
			}
		echo '</td>';
	echo '</tr>';
	foreach ($dept['positions'] as $position) {
		if ($position['active']) {
			echo_position($level + 1, $position);
		}
	}
	echo_other_positions($level + 1, $dept);
	foreach ($departments as $child) {
		if ($child['active'] && $child['parent-id'] == $dept['id']) {
			echo_department($level + 1, $child);
		}
	}
}

function echo_other_departments($level) {
	global $staff;
	foreach ($staff as $member) {
		foreach ($member['assigned-positions'] as $ap) {
			if (!$ap['department-id']) {
				echo_staff_member($level, $ap['position-name-h'], true, $member);
			}
		}
	}
}

function echo_root() {
	global $departments;
	foreach ($departments as $dept) {
		if ($dept['active'] && !$dept['parent-id']) {
			echo_department(0, $dept);
		}
	}
	echo_other_departments(0);
}

//Check if any staff are unassigned
if(count(array_filter($staff,function($st) {return $st['assigned-department-id'] ===-1;}))){
	//Yep, add in the dummy department and position!
	array_unshift($departments , array(
		'id' => -1,
		'parent-id' => null,
		'name' => '[[UNASSIGNED]]',
		'description' => 'Staff members that have no department',
		'active' => true,
		'hierarchy' => array(-1 => '[[UNASSIGNED]]'),
		'positions' => array(array(
			'id' => -1,
			'parent-id' => null,
			'order' => 1,
			'name' => '[[UNASSIGNED]]',
			'description' => 'Staff that have no assigned position',
			'executive' => false,
			'active' => true
		)),
	));
}

cm_admin_head('Org Chart');
cm_admin_body('Org Chart');
cm_admin_nav('staff-orgchart');
echo '<article class="cm-search-page">';

echo '<div class="cm-list-table">';
	echo '<table border="0" cellpadding="0" cellspacing="0">';
		echo '<thead>';
			echo '<tr>';
				echo '<th>Position</th>';
				echo '<th>Real Name</th>';
				echo '<th>Fandom Name</th>';
				echo '<th>Email Address</th>';
				echo '<th>Email Alias</th>';
				echo '<th>Phone Number</th>';
				if ($has_actions) echo '<th class="td-actions">Actions</th>';
			echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
			echo_root();
		echo '</tbody>';
	echo '</table>';
echo '</div>';

echo '</article>';
cm_admin_dialogs();
cm_admin_tail();
