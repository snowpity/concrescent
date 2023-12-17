<?php

require_once __DIR__ .'/../../lib/database/misc.php';
require_once __DIR__ .'/../../lib/database/staff.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('staff-maillist', 'staff-maillist');

$midb = new cm_misc_db($db);
if (isset($_POST['submit'])) {
	if (isset($_POST['mail-default-domain'])) $midb->setval('mail-default-domain', strtolower(trim($_POST['mail-default-domain'])));
	if (isset($_POST['mail-alias-execs-1'])) $midb->setval('mail-alias-execs-1', strtolower(trim($_POST['mail-alias-execs-1'])));
	if (isset($_POST['mail-alias-execs-2'])) $midb->setval('mail-alias-execs-2', strtolower(trim($_POST['mail-alias-execs-2'])));
	if (isset($_POST['mail-alias-staff-1'])) $midb->setval('mail-alias-staff-1', strtolower(trim($_POST['mail-alias-staff-1'])));
	if (isset($_POST['mail-alias-staff-2'])) $midb->setval('mail-alias-staff-2', strtolower(trim($_POST['mail-alias-staff-2'])));
	if (isset($_POST['mail-list-limit'])) $midb->setval('mail-list-limit', (int)trim($_POST['mail-list-limit']));
}
$domain = strtolower(trim($midb->getval('mail-default-domain', $_SERVER['SERVER_NAME'])));
$alias_execs_1 = strtolower(trim($midb->getval('mail-alias-execs-1', 'execs@'.$domain)));
$alias_execs_2 = strtolower(trim($midb->getval('mail-alias-execs-2', 'exec@'.$domain)));
$alias_staff_1 = strtolower(trim($midb->getval('mail-alias-staff-1', 'staff@'.$domain)));
$alias_staff_2 = strtolower(trim($midb->getval('mail-alias-staff-2', '')));
$list_limit = (int)trim($midb->getval('mail-list-limit', 100));

$sdb = new cm_staff_db($db);
$departments = $sdb->list_departments();
$departments = array_filter($departments, function($dept) {
	return $dept['active'];
});
$staff = $sdb->list_staff_members();
$staff = array_filter($staff, function($member) {
	return $member['email-address'] && $member['application-status'] == 'Accepted';
});

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

$known_personal_address_domains = array(
	"@aol.com", "@arnet.com.ar", "@att.net", "@bellsouth.net",
	"@blueyonder.co.uk", "@bt.com", "@btinternet.com", "@charter.net",
	"@comcast.net", "@cox.net", "@daum.net", "@earthlink.net", "@email.com",
	"@facebook.com", "@fastmail.fm", "@fibertel.com.ar", "@free.fr",
	"@freeserve.co.uk", "@games.com", "@gmail.com", "@gmx.com", "@gmx.de",
	"@gmx.fr", "@gmx.net", "@google.com", "@googlemail.com", "@hanmail.net",
	"@hotmail.be", "@hotmail.co.uk", "@hotmail.com.ar", "@hotmail.com.mx",
	"@hotmail.com", "@hotmail.de", "@hotmail.es", "@hotmail.fr", "@hush.com",
	"@hushmail.com", "@icloud.com", "@inbox.com", "@juno.com", "@laposte.net",
	"@lavabit.com", "@list.ru", "@live.be", "@live.co.uk", "@live.com.ar",
	"@live.com.mx", "@live.com", "@live.de", "@live.fr", "@love.com",
	"@mac.com", "@mail.com", "@mail.ru", "@me.com", "@msn.com", "@nate.com",
	"@naver.com", "@neuf.fr", "@ntlworld.com", "@o2.co.uk", "@online.de",
	"@orange.fr", "@orange.net", "@outlook.com", "@pobox.com",
	"@prodigy.net.mx", "@qq.com", "@rambler.ru", "@rocketmail.com",
	"@safe-mail.net", "@sbcglobal.net", "@sfr.fr", "@sina.com", "@sky.com",
	"@skynet.be", "@speedy.com.ar", "@t-online.de", "@talktalk.co.uk",
	"@telenet.be", "@tiscali.co.uk", "@tvcablenet.be", "@verizon.net",
	"@virgin.net", "@virginmedia.com", "@voo.be", "@wanadoo.co.uk",
	"@wanadoo.fr", "@web.de", "@wow.com", "@ya.ru", "@yahoo.co.id",
	"@yahoo.co.in", "@yahoo.co.jp", "@yahoo.co.kr", "@yahoo.co.uk",
	"@yahoo.com.ar", "@yahoo.com.mx", "@yahoo.com.ph", "@yahoo.com.sg",
	"@yahoo.com", "@yahoo.de", "@yahoo.fr", "@yandex.com", "@yandex.ru",
	"@ygm.com", "@ymail.com", "@zoho.com"
);
function is_known_personal_address($address) {
	global $known_personal_address_domains;
	$o = strpos($address, '@');
	if ($o === false) return false;
	$domain = substr($address, $o);
	return in_array($domain, $known_personal_address_domains);
}
function get_mail_aliases($item, $domain) {
	$aliases = array();
	if (isset($item['email-address'])) {
		$external_email = strtolower(trim($item['email-address']));
	} else {
		$external_email = null;
	}
	if (isset($item['mail-alias-1'])) {
		foreach (explode(',', $item['mail-alias-1']) as $alias) {
			$alias = strtolower(trim($alias));
			if ($alias && ($alias != $external_email) && !is_known_personal_address($alias)) {
				if (strpos($alias, '@') === false) {
					$alias .= '@' . $domain;
				}
				$aliases[] = $alias;
			}
		}
	}
	if (isset($item['mail-alias-2'])) {
		foreach (explode(',', $item['mail-alias-2']) as $alias) {
			$alias = strtolower(trim($alias));
			if ($alias && ($alias != $external_email) && !is_known_personal_address($alias)) {
				if (strpos($alias, '@') === false) {
					$alias .= '@' . $domain;
				}
				$aliases[] = $alias;
			}
		}
	}
	return $aliases;
}

function mail_aliases_append(&$aliases, $alias, $target) {
	if (isset($aliases[$alias])) {
		$aliases[$alias][] = $target;
	} else {
		$aliases[$alias] = array($target);
	}
}

$mailboxes_no_forwarding = array();
$mailboxes_with_forwarding = array();
$mail_aliases_personal = array();
$mail_aliases_departments = array();

$aliases_execs = get_mail_aliases(array(
	'mail-alias-1' => $alias_execs_1,
	'mail-alias-2' => $alias_execs_2
), $domain);
if ($aliases_execs) {
	$alias_execs = array_shift($aliases_execs);
	foreach ($aliases_execs as $alias) {
		mail_aliases_append($mail_aliases_departments, $alias, $alias_execs);
	}
} else {
	$alias_execs = null;
}

$aliases_staff = get_mail_aliases(array(
	'mail-alias-1' => $alias_staff_1,
	'mail-alias-2' => $alias_staff_2
), $domain);
if ($aliases_staff) {
	$alias_staff = array_shift($aliases_staff);
	foreach ($aliases_staff as $alias) {
		mail_aliases_append($mail_aliases_departments, $alias, $alias_staff);
	}
	if ($alias_execs) {
		mail_aliases_append($mail_aliases_departments, $alias_staff, $alias_execs);
	}
} else {
	$alias_staff = null;
}

$dept_mail_map = array();
foreach ($departments as $department) {
	$aliases = get_mail_aliases($department, $domain);
	if ($aliases) {
		$primary_email = array_shift($aliases);
		$execs_email = str_replace('@', '-execs@', $primary_email);
		$exec_email = str_replace('@', '-exec@', $primary_email);
		$staff_email = str_replace('@', '-staff@', $primary_email);
		$all_email = str_replace('@', '-all@', $primary_email);
		switch ($department['mail-depth']) {
			case 'Recursive':
				mail_aliases_append($mail_aliases_departments, $primary_email, $all_email);
				break;
			case 'Staff':
				mail_aliases_append($mail_aliases_departments, $primary_email, $staff_email);
				break;
			default:
				mail_aliases_append($mail_aliases_departments, $primary_email, $execs_email);
				break;
		}
		mail_aliases_append($mail_aliases_departments, $exec_email, $execs_email);
		mail_aliases_append($mail_aliases_departments, $staff_email, $execs_email);
		mail_aliases_append($mail_aliases_departments, $all_email, $staff_email);
		foreach ($aliases as $alias) {
			$execs_alias = str_replace('@', '-execs@', $alias);
			$exec_alias = str_replace('@', '-exec@', $alias);
			$staff_alias = str_replace('@', '-staff@', $alias);
			$all_alias = str_replace('@', '-all@', $alias);
			mail_aliases_append($mail_aliases_departments, $alias, $primary_email);
			mail_aliases_append($mail_aliases_departments, $execs_alias, $execs_email);
			mail_aliases_append($mail_aliases_departments, $exec_alias, $exec_email);
			mail_aliases_append($mail_aliases_departments, $staff_alias, $staff_email);
			mail_aliases_append($mail_aliases_departments, $all_alias, $all_email);
		}
		$dept_mail_map[$department['id']] = array($execs_email, $staff_email, $all_email);
	}
}

$pos_mail_map = array();
foreach ($departments as $department) {
	$id = $department['id'];
	if ($id && isset($dept_mail_map[$id])) {
		$parent_id = $department['parent-id'];
		if ($parent_id && isset($dept_mail_map[$parent_id])) {
			$child_all_email = $dept_mail_map[$id][2];
			$parent_all_email = $dept_mail_map[$parent_id][2];
			mail_aliases_append($mail_aliases_departments, $parent_all_email, $child_all_email);
		}
		if (isset($department['positions']) && $department['positions']) {
			foreach ($department['positions'] as $position) {
				if ($position['active']) {
					$idx = ($position['executive'] ? 0 : 1);
					$pos_mail_map[$position['id']] = $dept_mail_map[$id][$idx];
				}
			}
		}
	}
}

foreach ($staff as $staff_member) {
	$external_email = strtolower(trim($staff_member['email-address']));
	$aliases = get_mail_aliases($staff_member, $domain);
	if ($aliases) {
		$primary_email = array_shift($aliases);
		switch ($staff_member['mailbox-type']) {
			case 'Mailbox, No Forwarding':
				$mailboxes_no_forwarding[$primary_email] = $primary_email;
				break;
			case 'Mailbox, With Forwarding':
				mail_aliases_append($mailboxes_with_forwarding, $primary_email, $external_email);
				break;
			default:
				mail_aliases_append($mail_aliases_personal, $primary_email, $external_email);
				break;
		}
		foreach ($aliases as $alias) {
			mail_aliases_append($mail_aliases_personal, $alias, $primary_email);
		}
	} else {
		$primary_email = $external_email;
	}
	$is_exec = false;
	if (isset($staff_member['assigned-positions']) && $staff_member['assigned-positions']) {
		foreach ($staff_member['assigned-positions'] as $position) {
			$position_id = $position['position-id'];
			if ($position_id && isset($pos_mail_map[$position_id])) {
				$position_email = $pos_mail_map[$position_id];
				if (strpos($position_email, '-execs@') !== false) $is_exec = true;
				mail_aliases_append($mail_aliases_departments, $position_email, $primary_email);
			} else {
				$department_id = $position['department-id'];
				if ($department_id && isset($dept_mail_map[$department_id])) {
					$department_email = $dept_mail_map[$department_id][1];
					mail_aliases_append($mail_aliases_departments, $department_email, $primary_email);
				}
			}
		}
	}
	if ($is_exec && $alias_execs) {
		mail_aliases_append($mail_aliases_departments, $alias_execs, $primary_email);
	} else if ($alias_staff) {
		mail_aliases_append($mail_aliases_departments, $alias_staff, $primary_email);
	}
}

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

function echo_mailboxes($mailboxes) {
	usort($mailboxes, 'strnatcasecmp');
	foreach ($mailboxes as $mailbox) {
		echo htmlspecialchars($mailbox) . "\r\n";
	}
}

function echo_mail_aliases($aliases, $limit = 0) {
	uksort($aliases, 'strnatcasecmp');
	foreach ($aliases as $alias => $target) {
		usort($target, 'strnatcasecmp');
		if ($limit && count($target) > $limit) {
			$temp = array($alias => array());
			for ($i = 1, $o = 0, $n = count($target); $o < $n; $i++, $o += $limit) {
				$subalias = str_replace('@', '-'.$i.'@', $alias);
				$temp[$alias][] = $subalias;
				$temp[$subalias] = array_slice($target, $o, $limit);
			}
			foreach ($temp as $a => $t) {
				echo htmlspecialchars($a) . "\t";
				echo htmlspecialchars(implode(', ', $t)) . "\r\n";
			}
		} else {
			echo htmlspecialchars($alias) . "\t";
			echo htmlspecialchars(implode(', ', $target)) . "\r\n";
		}
	}
}

cm_admin_head('Mailing Lists');

?><style>
	textarea {
		width: 600px;
		height: 120px;
	}
</style><?php

cm_admin_body('Mailing Lists');
cm_admin_nav('staff-maillist');
echo '<article>';

echo '<div class="card">';
	echo '<div class="card-title">';
		echo 'Mailing Lists';
	echo '</div>';
	echo '<div class="card-content">';
		echo '<h3>Mailboxes - No Forwarding</h3>';
		echo '<p>';
			echo '<textarea readonly wrap="off">';
				echo_mailboxes($mailboxes_no_forwarding);
			echo '</textarea>';
		echo '</p>';
		echo '<h3>Mailboxes - With Forwarding</h3>';
		echo '<p>';
			echo '<textarea readonly wrap="off">';
				echo_mail_aliases($mailboxes_with_forwarding);
			echo '</textarea>';
		echo '</p>';
		echo '<h3>Mail Aliases - Personal</h3>';
		echo '<p>';
			echo '<textarea readonly wrap="off">';
				echo_mail_aliases($mail_aliases_personal, $list_limit);
			echo '</textarea>';
		echo '</p>';
		echo '<h3>Mail Aliases - Departments</h3>';
		echo '<p>';
			echo '<textarea readonly wrap="off">';
				echo_mail_aliases($mail_aliases_departments, $list_limit);
			echo '</textarea>';
		echo '</p>';
	echo '</div>';
echo '</div>';

echo '<form action="maillist.php" method="post" class="card">';
	echo '<div class="card-title">';
		echo 'Settings';
	echo '</div>';
	echo '<div class="card-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
			echo '<tr>';
				echo '<th><label for="mail-default-domain">Default Domain:</label></th>';
				echo '<td><input type="text" name="mail-default-domain" id="mail-default-domain" value="'.htmlspecialchars($domain).'"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="mail-alias-execs-1">Primary Email Alias for All Execs:</label></th>';
				echo '<td><input type="email" name="mail-alias-execs-1" id="mail-alias-execs-1" value="'.htmlspecialchars($alias_execs_1).'"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="mail-alias-execs-2">Secondary Email Alias for All Execs:</label></th>';
				echo '<td><input type="email" name="mail-alias-execs-2" id="mail-alias-execs-2" value="'.htmlspecialchars($alias_execs_2).'"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="mail-alias-staff-1">Primary Email Alias for All Staff:</label></th>';
				echo '<td><input type="email" name="mail-alias-staff-1" id="mail-alias-staff-1" value="'.htmlspecialchars($alias_staff_1).'"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="mail-alias-staff-2">Secondary Email Alias for All Staff:</label></th>';
				echo '<td><input type="email" name="mail-alias-staff-2" id="mail-alias-staff-2" value="'.htmlspecialchars($alias_staff_2).'"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="mail-list-limit">Mailing List Membership Limit:</label></th>';
				echo '<td><input type="number" name="mail-list-limit" id="mail-list-limit" value="'.htmlspecialchars($list_limit).'"></td>';
			echo '</tr>';
		echo '</table>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<input type="submit" name="submit" value="Save Changes">';
	echo '</div>';
echo '</form>';

echo '</article>';
cm_admin_dialogs();
cm_admin_tail();
