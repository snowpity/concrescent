<?php

require_once __DIR__ .'/../config/config.php';

$cm_admin_nav_home = (
	array(
		array(
			'id' => 'home',
			'href' => '/admin/index.php',
			'name' => 'Home',
			'description' => '',
			'permission' => ''
		),
		array(
			'id' => 'statistics',
			'href' => '/admin/statistics.php',
			'name' => 'Statistics',
			'description' => 'Get a statistical overview of people registered and checked in.',
			'permission' => 'statistics'
		),
	)
);

$cm_admin_nav_attendee = (
	array(
		array(
			'id' => 'attendees',
			'href' => '/admin/attendee/index.php',
			'name' => 'Attendees',
			'description' => 'View and modify attendee registration records.',
			'permission' => array('||', 'attendees', 'attendees-view', 'attendees-edit', 'attendees-delete')
		),
		array(
			'id' => 'attendee-badge-types',
			'href' => '/admin/attendee/badge-types.php',
			'name' => 'Attendee Badge Types',
			'description' => 'Create or modify the types of badges available to attendees.',
			'permission' => 'attendee-badge-types'
		),
		array(
			'id' => 'attendee-addons',
			'href' => '/admin/attendee/addons.php',
			'name' => 'Attendee Addons',
			'description' => 'Create or modify additional, optional items or packages for attendees.',
			'permission' => 'attendee-addons'
		),
		array(
			'id' => 'attendee-questions',
			'href' => '/admin/attendee/questions.php',
			'name' => 'Attendee Questions',
			'description' => 'Add explanatory text and questions to the attendee registration form.',
			'permission' => 'attendee-questions'
		),
		array(
			'id' => 'attendee-promo-codes',
			'href' => '/admin/attendee/promo-codes.php',
			'name' => 'Attendee Promo Codes',
			'description' => 'Add or remove codes for discounts on badges for attendees.',
			'permission' => 'attendee-promo-codes'
		),
		array(
			'id' => 'attendee-blacklist',
			'href' => '/admin/attendee/blacklist.php',
			'name' => 'Attendee Blacklist',
			'description' => 'Block certain people from being able to register as attendees.',
			'permission' => 'attendee-blacklist'
		),
		array(
			'id' => 'attendee-mail',
			'href' => '/admin/attendee/mail.php',
			'name' => 'Attendee Form Letters',
			'description' => 'Write form letters to be emailed to attendees.',
			'permission' => 'attendee-mail'
		),
		array(
			'id' => 'attendee-export',
			'href' => '/admin/attendee/export.php',
			'name' => 'Attendee File Export',
			'description' => 'Download a file of attendee registration records.',
			'permission' => 'attendee-csv'
		),
		array(
			'id' => 'attendee-sponsors',
			'href' => '/admin/attendee/sponsors.php',
			'name' => 'Attendee Sponsors',
			'description' => 'Get public sponsors from a Json endpoint',
			'permission' => 'attendee-csv'
		),
	)
);

$cm_admin_nav_application_common = (
	array(
		array(
			'id' => 'rooms-and-tables',
			'href' => '/admin/rooms-and-tables.php',
			'name' => 'Rooms & Tables',
			'description' => 'Upload an event space floor plan and tag rooms and tables with their identifiers.',
			'permission' => 'rooms-and-tables'
		),
	)
);

function cm_admin_nav_application($context, $ctx_info) {
	$ctx_lc = strtolower($context);
	$ctx_name = $ctx_info['nav_prefix'];
	$ctx_name_lc = strtolower($ctx_name);
	return array(
		array(
			'id' => 'applications-'.$ctx_lc,
			'href' => '/admin/application/index.php?c='.$ctx_lc,
			'name' => $ctx_name.' Applications',
			'description' => 'Review, approve, or modify '.$ctx_name_lc.' applications.',
			'permission' => array('||',
				'applications-'.$ctx_lc,
				'applications-view-'.$ctx_lc,
				'applications-review-'.$ctx_lc,
				'applications-edit-'.$ctx_lc,
				'applications-delete-'.$ctx_lc
			)
		),
		array(
			'id' => 'applicants-'.$ctx_lc,
			'href' => '/admin/application/badge-index.php?c='.$ctx_lc,
			'name' => $ctx_name.' Badges',
			'description' => 'View or modify '.$ctx_name_lc.' badge registration records.',
			'permission' => array('||',
				'applicants-'.$ctx_lc,
				'applicants-view-'.$ctx_lc,
				'applicants-edit-'.$ctx_lc,
				'applicants-delete-'.$ctx_lc
			)
		),
		array(
			'id' => 'application-assignments-'.$ctx_lc,
			'href' => '/admin/application/assignments.php?c='.$ctx_lc,
			'name' => $ctx_name.' Assignments',
			'description' => 'View and edit '.strtolower($ctx_info['assignment_term'][1]).' assigned to '.$ctx_name_lc.' applications.',
			'permission' => 'application-assignments-'.$ctx_lc
		),
		array(
			'id' => 'application-badge-types-'.$ctx_lc,
			'href' => '/admin/application/badge-types.php?c='.$ctx_lc,
			'name' => $ctx_name.' Badge Types',
			'description' => 'Create or modify the types of badges available on the '.$ctx_name_lc.' application form.',
			'permission' => 'application-badge-types-'.$ctx_lc
		),
		array(
			'id' => 'application-questions-'.$ctx_lc,
			'href' => '/admin/application/questions.php?c='.$ctx_lc,
			'name' => $ctx_name.' Questions',
			'description' => 'Add explanatory text and questions to the '.$ctx_name_lc.' application form.',
			'permission' => 'application-questions-'.$ctx_lc
		),
		array(
			'id' => 'application-blacklist-'.$ctx_lc,
			'href' => '/admin/application/app-blacklist.php?c='.$ctx_lc,
			'name' => $ctx_name.' App Blacklist',
			'description' => 'Inform people reviewing '.$ctx_name_lc.' applications of certain applications that should not be accepted.',
			'permission' => 'application-blacklist-'.$ctx_lc
		),
		array(
			'id' => 'applicant-blacklist-'.$ctx_lc,
			'href' => '/admin/application/badge-blacklist.php?c='.$ctx_lc,
			'name' => $ctx_name.' Badge Blacklist',
			'description' => 'Inform people reviewing '.$ctx_name_lc.' applications of certain people who should not receive badges.',
			'permission' => 'applicant-blacklist-'.$ctx_lc
		),
		array(
			'id' => 'application-mail-'.$ctx_lc,
			'href' => '/admin/application/mail.php?c='.$ctx_lc,
			'name' => $ctx_name.' Form Letters',
			'description' => 'Write form letters to be emailed to '.$ctx_name_lc.' applicants.',
			'permission' => 'application-mail-'.$ctx_lc
		),
		array(
			'id' => 'application-csv-'.$ctx_lc,
			'href' => '/admin/application/csv.php?c='.$ctx_lc,
			'name' => $ctx_name.' CSV Export',
			'description' => 'Download a CSV file of '.$ctx_name_lc.' applications or badge registration records.',
			'permission' => 'application-csv-'.$ctx_lc
		),
	);
}

$cm_admin_nav_staff_departments = (
	array(
		array(
			'id' => 'staff-departments',
			'href' => '/admin/staff/departments.php',
			'name' => 'Departments',
			'description' => 'Organize the departments and positions that make up the event staff org chart.',
			'permission' => 'staff-departments'
		),
		array(
			'id' => 'staff-orgchart',
			'href' => '/admin/staff/orgchart.php',
			'name' => 'Org Chart',
			'description' => 'View the event staff org chart.',
			'permission' => 'staff-orgchart'
		),
		array(
			'id' => 'staff-maillist',
			'href' => '/admin/staff/maillist.php',
			'name' => 'Mailing Lists',
			'description' => 'Generate mailing list memberships based on staff applications and the org chart.',
			'permission' => 'staff-maillist'
		),
	)
);

$cm_admin_nav_staff = (
	array(
		array(
			'id' => 'staff',
			'href' => '/admin/staff/index.php',
			'name' => 'Staff Applications',
			'description' => 'Review, approve, or modify staff applications.',
			'permission' => array('||', 'staff', 'staff-view', 'staff-review', 'staff-edit', 'staff-delete')
		),
		array(
			'id' => 'staff-badge-types',
			'href' => '/admin/staff/badge-types.php',
			'name' => 'Staff Badge Types',
			'description' => 'Create or modify the types of badges available for staff members.',
			'permission' => 'staff-badge-types'
		),
		array(
			'id' => 'staff-questions',
			'href' => '/admin/staff/questions.php',
			'name' => 'Staff Questions',
			'description' => 'Add explanatory text and questions to the staff application form.',
			'permission' => 'staff-questions'
		),
		array(
			'id' => 'staff-blacklist',
			'href' => '/admin/staff/blacklist.php',
			'name' => 'Staff Blacklist',
			'description' => 'Inform people reviewing staff applications of certain people who should not be accepted.',
			'permission' => 'staff-blacklist'
		),
		array(
			'id' => 'staff-mail',
			'href' => '/admin/staff/mail.php',
			'name' => 'Staff Form Letters',
			'description' => 'Write form letters to be emailed to staff members.',
			'permission' => 'staff-mail'
		),
		array(
			'id' => 'staff-csv',
			'href' => '/admin/staff/csv.php',
			'name' => 'Staff CSV Export',
			'description' => 'Download a CSV file of staff application records.',
			'permission' => 'staff-csv'
		),
	)
);

$cm_admin_nav_badge = (
	array(
		array(
			'id' => 'badge-checkin',
			'href' => '/admin/badge/checkin.php',
			'name' => 'Registration Check-In',
			'description' => 'Check in people at registration.',
			'permission' => 'badge-checkin'
		),
		array(
			'id' => 'badge-artwork',
			'href' => '/admin/badge/artwork.php',
			'name' => 'Badge Artwork',
			'description' => 'Upload badge artwork and add text fields for names and ID numbers.',
			'permission' => 'badge-artwork'
		),
		array(
			'id' => 'badge-preprinting',
			'href' => '/admin/badge/preprinting.php',
			'name' => 'Badge Pre-Printing',
			'description' => 'Print badges by badge type.',
			'permission' => 'badge-preprinting'
		),
		array(
			'id' => 'badge-oneoff-printing',
			'href' => '/admin/badge/oneoff-printing.php',
			'name' => 'One-Off Badge Printing',
			'description' => 'Print one-off badges for badge holders not in CONcrescent.',
			'permission' => 'badge-oneoff-printing'
		),
		array(
			'id' => 'badge-printing-setup',
			'href' => '/admin/badge/printing-setup.php',
			'name' => 'Badge Printing Setup',
			'description' => 'Change badge size and other settings for badge printing.',
			'permission' => 'badge-printing-setup'
		),
	)
);

$cm_admin_nav_payment = (
	array(
		array(
			'id' => 'payment-request',
			'href' => '/admin/payment/request.php',
			'name' => 'Request Payment',
			'description' => 'Request payments from individuals for purposes not covered elsewhere.',
			'permission' => 'payment-request'
		),
		array(
			'id' => 'payments',
			'href' => '/admin/payment/index.php',
			'name' => 'Payment Requests',
			'description' => 'View and modify payment request records.',
			'permission' => array('||', 'payments', 'payments-view', 'payments-edit', 'payments-delete')
		),
		array(
			'id' => 'payment-mail',
			'href' => '/admin/payment/mail-index.php',
			'name' => 'Payment Form Letters',
			'description' => 'Write form letters to be emailed upon requesting payment.',
			'permission' => 'payment-mail'
		),
		array(
			'id' => 'payment-csv',
			'href' => '/admin/payment/csv.php',
			'name' => 'Payment CSV Export',
			'description' => 'Download a CSV file of payment request records.',
			'permission' => 'payment-csv'
		),
	)
);

$cm_admin_nav_admin = (
	array(
		array(
			'id' => 'admin-users',
			'href' => '/admin/users.php',
			'name' => 'Admin Users',
			'description' => 'Manage CONcrescent administrators and their permissions.',
			'permission' => 'admin-users'
		),
		array(
			'id' => 'admin-user',
			'href' => '/admin/user.php',
			'name' => 'Account Settings',
			'description' => 'Change your user name or password.',
			'permission' => ''
		),
		array(
			'id' => 'logout',
			'href' => '/admin/logout.php',
			'name' => 'Log Out',
			'description' => 'Log out of CONcrescent.',
			'permission' => ''
		),
	)
);

$cm_admin_nav = array();
$cm_admin_nav[] = $cm_admin_nav_home;
$cm_admin_nav[] = $cm_admin_nav_attendee;

$cm_admin_nav[] = $cm_admin_nav_application_common;
foreach ($cm_config['application_types'] as $context => $ctx_info) {
	$cm_admin_nav[] = cm_admin_nav_application($context, $ctx_info);
}

$cm_admin_nav[] = $cm_admin_nav_staff_departments;
$cm_admin_nav[] = $cm_admin_nav_staff;
$cm_admin_nav[] = $cm_admin_nav_badge;
$cm_admin_nav[] = $cm_admin_nav_payment;
$cm_admin_nav[] = $cm_admin_nav_admin;
