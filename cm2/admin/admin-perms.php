<?php

require_once __DIR__ .'/../config/config.php';

$cm_admin_perms_home = (
	array(
		array(
			'id' => 'statistics',
			'name' => 'Statistics',
			'description' => 'Get a statistical overview of people registered and checked in.'
		),
	)
);

$cm_admin_perms_attendee = (
	array(
		array(
			'id' => 'attendees',
			'name' => 'Attendees',
			'description' => 'View the list of attendee registration records.'
		),
		array(
			'id' => 'attendees-view',
			'name' => 'Attendees - View',
			'description' => 'View individual attendee registration records.'
		),
		array(
			'id' => 'attendees-edit',
			'name' => 'Attendees - Edit',
			'description' => 'Modify attendee registration records.'
		),
		array(
			'id' => 'attendees-delete',
			'name' => 'Attendees - Delete',
			'description' => 'Delete attendee registration records.'
		),
		array(
			'id' => 'attendees-refund',
			'name' => 'Attendees - Refund',
			'description' => 'Refund attendee registrations.'
		),
	)
);

$cm_admin_perms_attendee_config = (
	array(
		array(
			'id' => 'attendee-badge-types',
			'name' => 'Attendee Badge Types',
			'description' => 'Create or modify the types of badges available to attendees.'
		),
		array(
			'id' => 'attendee-addons',
			'name' => 'Attendee Addons',
			'description' => 'Create or modify additional, optional items or packages for attendees.'
		),
		array(
			'id' => 'attendee-questions',
			'name' => 'Attendee Questions',
			'description' => 'Add explanatory text and questions to the attendee registration form.'
		),
		array(
			'id' => 'attendee-promo-codes',
			'name' => 'Attendee Promo Codes',
			'description' => 'Add or remove codes for discounts on badges for attendees.'
		),
		array(
			'id' => 'attendee-blacklist',
			'name' => 'Attendee Blacklist',
			'description' => 'Block certain people from being able to register as attendees.'
		),
		array(
			'id' => 'attendee-mail',
			'name' => 'Attendee Form Letters',
			'description' => 'Write form letters to be emailed to attendees.'
		),
		array(
			'id' => 'attendee-csv',
			'name' => 'Attendee CSV Export',
			'description' => 'Download a CSV file of attendee registration records.'
		),
	)
);

$cm_admin_perms_application_common = (
	array(
		array(
			'id' => 'rooms-and-tables',
			'name' => 'Rooms & Tables',
			'description' => 'Upload an event space floor plan and tag rooms and tables with their identifiers.'
		),
	)
);

function cm_admin_perms_application($context, $ctx_info) {
	$ctx_lc = strtolower($context);
	$ctx_name = $ctx_info['nav_prefix'];
	$ctx_name_lc = strtolower($ctx_name);
	return array(
		array(
			'id' => 'applications-'.$ctx_lc,
			'name' => $ctx_name.' Applications',
			'description' => 'View the list of '.$ctx_name_lc.' applications.'
		),
		array(
			'id' => 'applications-view-'.$ctx_lc,
			'name' => $ctx_name.' Applications - View',
			'description' => 'View individual '.$ctx_name_lc.' applications.'
		),
		array(
			'id' => 'applications-review-'.$ctx_lc,
			'name' => $ctx_name.' Applications - Review',
			'description' => 'Review and approve '.$ctx_name_lc.' applications.'
		),
		array(
			'id' => 'applications-edit-'.$ctx_lc,
			'name' => $ctx_name.' Applications - Edit',
			'description' => 'Modify '.$ctx_name_lc.' applications.'
		),
		array(
			'id' => 'applications-delete-'.$ctx_lc,
			'name' => $ctx_name.' Applications - Delete',
			'description' => 'Delete '.$ctx_name_lc.' applications.'
		),
	);
}

function cm_admin_perms_applicant($context, $ctx_info) {
	$ctx_lc = strtolower($context);
	$ctx_name = $ctx_info['nav_prefix'];
	$ctx_name_lc = strtolower($ctx_name);
	return array(
		array(
			'id' => 'applicants-'.$ctx_lc,
			'name' => $ctx_name.' Badges',
			'description' => 'View the list of '.$ctx_name_lc.' badge registration records.'
		),
		array(
			'id' => 'applicants-view-'.$ctx_lc,
			'name' => $ctx_name.' Badges - View',
			'description' => 'View individual '.$ctx_name_lc.' badge registration records.'
		),
		array(
			'id' => 'applicants-edit-'.$ctx_lc,
			'name' => $ctx_name.' Badges - Edit',
			'description' => 'Modify '.$ctx_name_lc.' badge registration records.'
		),
		array(
			'id' => 'applicants-delete-'.$ctx_lc,
			'name' => $ctx_name.' Badges - Delete',
			'description' => 'Delete '.$ctx_name_lc.' badge registration records.'
		),
	);
}

function cm_admin_perms_application_config($context, $ctx_info) {
	$ctx_lc = strtolower($context);
	$ctx_name = $ctx_info['nav_prefix'];
	$ctx_name_lc = strtolower($ctx_name);
	return array(
		array(
			'id' => 'application-assignments-'.$ctx_lc,
			'name' => $ctx_name.' Assignments',
			'description' => 'View and edit '.strtolower($ctx_info['assignment_term'][1]).' assigned to '.$ctx_name_lc.' applications.'
		),
		array(
			'id' => 'application-badge-types-'.$ctx_lc,
			'name' => $ctx_name.' Badge Types',
			'description' => 'Create or modify the types of badges available on the '.$ctx_name_lc.' application form.'
		),
		array(
			'id' => 'application-questions-'.$ctx_lc,
			'name' => $ctx_name.' Questions',
			'description' => 'Add explanatory text and questions to the '.$ctx_name_lc.' application form.'
		),
		array(
			'id' => 'application-blacklist-'.$ctx_lc,
			'name' => $ctx_name.' App Blacklist',
			'description' => 'Inform people reviewing '.$ctx_name_lc.' applications of certain applications that should not be accepted.'
		),
		array(
			'id' => 'applicant-blacklist-'.$ctx_lc,
			'name' => $ctx_name.' Badge Blacklist',
			'description' => 'Inform people reviewing '.$ctx_name_lc.' applications of certain people who should not receive badges.'
		),
		array(
			'id' => 'application-mail-'.$ctx_lc,
			'name' => $ctx_name.' Form Letters',
			'description' => 'Write form letters to be emailed to '.$ctx_name_lc.' applicants.'
		),
		array(
			'id' => 'application-csv-'.$ctx_lc,
			'name' => $ctx_name.' CSV Export',
			'description' => 'Download a CSV file of '.$ctx_name_lc.' applications or badge registration records.'
		),
	);
};

$cm_admin_perms_staff_departments = (
	array(
		array(
			'id' => 'staff-departments',
			'name' => 'Departments',
			'description' => 'Organize the departments and positions that make up the event staff org chart.'
		),
		array(
			'id' => 'staff-orgchart',
			'name' => 'Org Chart',
			'description' => 'View the event staff org chart.'
		),
		array(
			'id' => 'staff-maillist',
			'name' => 'Mailing Lists',
			'description' => 'Generate mailing list memberships based on staff applications and the org chart.'
		),
	)
);

$cm_admin_perms_staff = (
	array(
		array(
			'id' => 'staff',
			'name' => 'Staff Applications',
			'description' => 'View the list of staff applications.'
		),
		array(
			'id' => 'staff-view',
			'name' => 'Staff Applications - View',
			'description' => 'View individual staff applications.'
		),
		array(
			'id' => 'staff-review',
			'name' => 'Staff Applications - Review',
			'description' => 'Review and approve staff applications.'
		),
		array(
			'id' => 'staff-edit',
			'name' => 'Staff Applications - Edit',
			'description' => 'Modify staff applications.'
		),
		array(
			'id' => 'staff-delete',
			'name' => 'Staff Applications - Delete',
			'description' => 'Delete staff applications.'
		),
	)
);

$cm_admin_perms_staff_config = (
	array(
		array(
			'id' => 'staff-badge-types',
			'name' => 'Staff Badge Types',
			'description' => 'Create or modify the types of badges available for staff members.'
		),
		array(
			'id' => 'staff-questions',
			'name' => 'Staff Questions',
			'description' => 'Add explanatory text and questions to the staff application form.'
		),
		array(
			'id' => 'staff-blacklist',
			'name' => 'Staff Blacklist',
			'description' => 'Inform people reviewing staff applications of certain people who should not be accepted.'
		),
		array(
			'id' => 'staff-mail',
			'name' => 'Staff Form Letters',
			'description' => 'Write form letters to be emailed to staff members.'
		),
		array(
			'id' => 'staff-csv',
			'name' => 'Staff CSV Export',
			'description' => 'Download a CSV file of staff application records.'
		),
	)
);

$cm_admin_perms_badge = (
	array(
		array(
			'id' => 'badge-checkin',
			'name' => 'Registration Check-In',
			'description' => 'Check in people at registration.'
		),
		array(
			'id' => 'badge-artwork',
			'name' => 'Badge Artwork',
			'description' => 'Upload badge artwork and add text fields for names and ID numbers.'
		),
		array(
			'id' => 'badge-preprinting',
			'name' => 'Badge Pre-Printing',
			'description' => 'Print badges by badge type.'
		),
		array(
			'id' => 'badge-oneoff-printing',
			'name' => 'One-Off Badge Printing',
			'description' => 'Print one-off badges for badge holders not in CONcrescent.'
		),
		array(
			'id' => 'badge-printing-setup',
			'name' => 'Badge Printing Setup',
			'description' => 'Change badge size and other settings for badge printing.'
		),
	)
);

$cm_admin_perms_payment = (
	array(
		array(
			'id' => 'payment-request',
			'name' => 'Request Payment',
			'description' => 'Request payments from individuals for purposes not covered elsewhere.'
		),
		array(
			'id' => 'payments',
			'name' => 'Payment Requests',
			'description' => 'View the list of payment request records.'
		),
		array(
			'id' => 'payments-view',
			'name' => 'Payment Requests - View',
			'description' => 'View individual payment request records.'
		),
		array(
			'id' => 'payments-edit',
			'name' => 'Payment Requests - Edit',
			'description' => 'Modify payment request records.'
		),
		array(
			'id' => 'payments-delete',
			'name' => 'Payment Requests - Delete',
			'description' => 'Delete payment request records.'
		),
		array(
			'id' => 'payment-mail',
			'name' => 'Payment Form Letters',
			'description' => 'Write form letters to be emailed upon requesting payment.'
		),
		array(
			'id' => 'payment-csv',
			'name' => 'Payment CSV Export',
			'description' => 'Download a CSV file of payment request records.'
		),
	)
);

$cm_admin_perms_admin = (
	array(
		array(
			'id' => 'admin-users',
			'name' => 'Admin Users',
			'description' => 'Manage CONcrescent administrators and their permissions.'
		),
	)
);

$cm_admin_perms_all = (
	array(
		array(
			'id' => '*',
			'name' => 'ALL',
			'description' => 'Grant all possible permissions to this user.'
		),
	)
);

$cm_admin_perms = array();
$cm_admin_perms[] = $cm_admin_perms_home;
$cm_admin_perms[] = $cm_admin_perms_attendee;
$cm_admin_perms[] = $cm_admin_perms_attendee_config;

$cm_admin_perms[] = $cm_admin_perms_application_common;
foreach ($cm_config['application_types'] as $context => $ctx_info) {
	$cm_admin_perms[] = cm_admin_perms_application($context, $ctx_info);
	$cm_admin_perms[] = cm_admin_perms_applicant($context, $ctx_info);
	$cm_admin_perms[] = cm_admin_perms_application_config($context, $ctx_info);
}

$cm_admin_perms[] = $cm_admin_perms_staff_departments;
$cm_admin_perms[] = $cm_admin_perms_staff;
$cm_admin_perms[] = $cm_admin_perms_staff_config;
$cm_admin_perms[] = $cm_admin_perms_badge;
$cm_admin_perms[] = $cm_admin_perms_payment;
$cm_admin_perms[] = $cm_admin_perms_admin;
$cm_admin_perms[] = $cm_admin_perms_all;
