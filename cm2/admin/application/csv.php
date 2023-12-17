<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../../lib/database/application.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../../lib/util/cmcsv.php';
require_once __DIR__ .'/../admin.php';

$context = (isset($_GET['c']) ? trim($_GET['c']) : null);
if (!$context) {
	header('Location: ../');
	exit(0);
}
$ctx_lc = strtolower($context);
$ctx_uc = strtoupper($context);
$ctx_info = (
	$cm_config['application_types'][$ctx_uc] ?? null
);
if (!$ctx_info) {
	header('Location: ../');
	exit(0);
}
$ctx_name = $ctx_info['nav_prefix'];
$ctx_name_lc = strtolower($ctx_name);

cm_admin_check_permission('application-csv-'.$ctx_lc, 'application-csv-'.$ctx_lc);

$apdb = new cm_application_db($db, $context);
$name_map = $apdb->get_badge_type_name_map();

$fdb = new cm_forms_db($db, 'application-'.$ctx_lc);
$questions = $fdb->list_questions();

if (isset($_POST['download-applications'])) {
	$columns = array_merge(
		array(
			array('key' => 'id',                               'name' => 'ID',                                         'type' => 'int'  ),
			array('key' => 'id-string',                        'name' => 'ID String',                                  'type' => 'text' ),
			array('key' => 'contact-first-name',               'name' => 'Contact First Name',                         'type' => 'text' ),
			array('key' => 'contact-last-name',                'name' => 'Contact Last Name',                          'type' => 'text' ),
			array('key' => 'contact-real-name',                'name' => 'Contact Real Name',                          'type' => 'text' ),
			array('key' => 'contact-email-address',            'name' => 'Contact Email Address',                      'type' => 'text' ),
			array('key' => 'contact-email-address-subscribed', 'name' => 'Contact Email Address (Subscribed)',         'type' => 'text' ),
			array('key' => 'contact-subscribed',               'name' => 'Contact Subscribed',                         'type' => 'bool' ),
			array('key' => 'contact-unsubscribe-link',         'name' => 'Contact Unsubscribe Link',                   'type' => 'text' ),
			array('key' => 'contact-phone-number',             'name' => 'Contact Phone Number',                       'type' => 'text' ),
			array('key' => 'contact-address-1',                'name' => 'Contact Street Address (First Line)',        'type' => 'text' ),
			array('key' => 'contact-address-2',                'name' => 'Contact Street Address (Second Line)',       'type' => 'text' ),
			array('key' => 'contact-address',                  'name' => 'Contact Street Address',                     'type' => 'text' ),
			array('key' => 'contact-city',                     'name' => 'Contact City',                               'type' => 'text' ),
			array('key' => 'contact-state',                    'name' => 'Contact State or Province',                  'type' => 'text' ),
			array('key' => 'contact-zip-code',                 'name' => 'Contact ZIP or Postal Code',                 'type' => 'text' ),
			array('key' => 'contact-csz',                      'name' => 'Contact City, State, ZIP',                   'type' => 'text' ),
			array('key' => 'contact-country',                  'name' => 'Contact Country',                            'type' => 'text' ),
			array('key' => 'contact-address-full',             'name' => 'Contact Street Address (Full)',              'type' => 'text' ),
			array('key' => 'badge-type-id',                    'name' => 'Badge Type ID',                              'type' => 'int'  ),
			array('key' => 'badge-type-id-string',             'name' => 'Badge Type ID String',                       'type' => 'text' ),
			array('key' => 'badge-type-name',                  'name' => 'Badge Type Name',                            'type' => 'text' ),
			array('key' => 'business-name',                    'name' => $ctx_info['business_name_term'],              'type' => 'text' ),
			array('key' => 'application-name',                 'name' => $ctx_info['application_name_term'],           'type' => 'text' ),
			array('key' => 'assignment-count',                 'name' => $ctx_info['assignment_term'][1].' Requested', 'type' => 'int'  ),
			array('key' => 'applicant-count',                  'name' => 'Badges Requested',                           'type' => 'int'  ),
			array('key' => 'min-birthdate',                    'name' => 'Earliest Date of Birth',                     'type' => 'text' ),
			array('key' => 'max-birthdate',                    'name' => 'Latest Date of Birth',                       'type' => 'text' ),
			array('key' => 'min-age',                          'name' => 'Youngest Age (Start of Event)',              'type' => 'int'  ),
			array('key' => 'max-age',                          'name' => 'Eldest Age (Start of Event)',                'type' => 'int'  ),
		),
		cm_form_questions_to_csv_columns($questions),
		array(
			array('key' => 'application-status',               'name' => 'Application Status',                         'type' => 'text' ),
			array('key' => 'assigned-room-or-table-id',        'name' => 'Assigned Room or Table ID',                  'type' => 'text' ),
			array('key' => 'assigned-start-time',              'name' => 'Assigned Start Time',                        'type' => 'text' ),
			array('key' => 'assigned-end-time',                'name' => 'Assigned End Time',                          'type' => 'text' ),
			array('key' => 'assigned-room-and-table-ids',      'name' => 'Assigned Room and Table IDs',                'type' => 'array'),
			array('key' => 'assigned-start-times',             'name' => 'Assigned Start Times',                       'type' => 'array'),
			array('key' => 'assigned-end-times',               'name' => 'Assigned End Times',                         'type' => 'array'),
			array('key' => 'permit-number',                    'name' => 'Permit Number',                              'type' => 'text' ),
			array('key' => 'payment-status',                   'name' => 'Payment Status',                             'type' => 'text' ),
			array('key' => 'payment-badge-price',              'name' => 'Payment Badge Price',                        'type' => 'price'),
			array('key' => 'payment-group-uuid',               'name' => 'Payment Group UUID',                         'type' => 'text' ),
			array('key' => 'payment-type',                     'name' => 'Payment Type',                               'type' => 'text' ),
			array('key' => 'payment-txn-id',                   'name' => 'Payment Transaction ID',                     'type' => 'text' ),
			array('key' => 'payment-txn-amt',                  'name' => 'Payment Transaction Amount',                 'type' => 'price'),
			array('key' => 'payment-date',                     'name' => 'Payment Date',                               'type' => 'text' ),
			array('key' => 'payment-details',                  'name' => 'Payment Details',                            'type' => 'text' ),
			array('key' => 'review-link',                      'name' => 'Review Order Link',                          'type' => 'text' ),
			array('key' => 'uuid',                             'name' => 'UUID',                                       'type' => 'text' ),
			array('key' => 'qr-data',                          'name' => 'QR Code Data',                               'type' => 'text' ),
			array('key' => 'qr-url',                           'name' => 'QR Code URL',                                'type' => 'text' ),
			array('key' => 'date-created',                     'name' => 'Date Created',                               'type' => 'text' ),
			array('key' => 'date-modified',                    'name' => 'Date Modified',                              'type' => 'text' ),
			array('key' => 'notes',                            'name' => 'Notes',                                      'type' => 'text' ),
		)
	);
	$entities = $apdb->list_applications(null, null, false, $name_map, $fdb);
	cm_output_csv($columns, $entities, 'applications.csv');
}

if (isset($_POST['download-applicants'])) {
	$columns = array(
			array('key' => 'id',                        'name' => 'ID',                               'type' => 'int'  ),
			array('key' => 'id-string',                 'name' => 'ID String',                        'type' => 'text' ),
			array('key' => 'first-name',                'name' => 'First Name',                       'type' => 'text' ),
			array('key' => 'last-name',                 'name' => 'Last Name',                        'type' => 'text' ),
			array('key' => 'real-name',                 'name' => 'Real Name',                        'type' => 'text' ),
			array('key' => 'fandom-name',               'name' => 'Fandom Name',                      'type' => 'text' ),
			array('key' => 'name-on-badge',             'name' => 'Name on Badge',                    'type' => 'text' ),
			array('key' => 'only-name',                 'name' => 'Only Name',                        'type' => 'text' ),
			array('key' => 'large-name',                'name' => 'Large Name',                       'type' => 'text' ),
			array('key' => 'small-name',                'name' => 'Small Name',                       'type' => 'text' ),
			array('key' => 'display-name',              'name' => 'Display Name',                     'type' => 'text' ),
			array('key' => 'date-of-birth',             'name' => 'Date of Birth',                    'type' => 'text' ),
			array('key' => 'age',                       'name' => 'Age (Start of Event)',             'type' => 'int'  ),
			array('key' => 'attendee-id',               'name' => 'Already Registered',               'type' => 'bool' ),
			array('key' => 'attendee-id',               'name' => 'Attendee ID',                      'type' => 'int'  ),
			array('key' => 'attendee-id-string',        'name' => 'Attendee ID String',               'type' => 'text' ),
			array('key' => 'application-id',            'name' => 'Application ID',                   'type' => 'int'  ),
			array('key' => 'application-id-string',     'name' => 'Application ID String',            'type' => 'text' ),
			array('key' => 'badge-type-id',             'name' => 'Badge Type ID',                    'type' => 'int'  ),
			array('key' => 'badge-type-id-string',      'name' => 'Badge Type ID String',             'type' => 'text' ),
			array('key' => 'badge-type-name',           'name' => 'Badge Type Name',                  'type' => 'text' ),
			array('key' => 'business-name',             'name' => $ctx_info['business_name_term'],    'type' => 'text' ),
			array('key' => 'application-name',          'name' => $ctx_info['application_name_term'], 'type' => 'text' ),
			array('key' => 'email-address',             'name' => 'Email Address',                    'type' => 'text' ),
			array('key' => 'email-address-subscribed',  'name' => 'Email Address (Subscribed)',       'type' => 'text' ),
			array('key' => 'subscribed',                'name' => 'Subscribed',                       'type' => 'bool' ),
			array('key' => 'unsubscribe-link',          'name' => 'Unsubscribe Link',                 'type' => 'text' ),
			array('key' => 'phone-number',              'name' => 'Phone Number',                     'type' => 'text' ),
			array('key' => 'address-1',                 'name' => 'Street Address (First Line)',      'type' => 'text' ),
			array('key' => 'address-2',                 'name' => 'Street Address (Second Line)',     'type' => 'text' ),
			array('key' => 'address',                   'name' => 'Street Address',                   'type' => 'text' ),
			array('key' => 'city',                      'name' => 'City',                             'type' => 'text' ),
			array('key' => 'state',                     'name' => 'State or Province',                'type' => 'text' ),
			array('key' => 'zip-code',                  'name' => 'ZIP or Postal Code',               'type' => 'text' ),
			array('key' => 'csz',                       'name' => 'City, State, ZIP',                 'type' => 'text' ),
			array('key' => 'country',                   'name' => 'Country',                          'type' => 'text' ),
			array('key' => 'address-full',              'name' => 'Street Address (Full)',            'type' => 'text' ),
			array('key' => 'ice-name',                  'name' => 'Emergency Contact Name',           'type' => 'text' ),
			array('key' => 'ice-relationship',          'name' => 'Emergency Contact Relationship',   'type' => 'text' ),
			array('key' => 'ice-email-address',         'name' => 'Emergency Contact Email Address',  'type' => 'text' ),
			array('key' => 'ice-phone-number',          'name' => 'Emergency Contact Phone Number',   'type' => 'text' ),
			array('key' => 'application-status',        'name' => 'Application Status',               'type' => 'text' ),
			array('key' => 'assigned-room-or-table-id', 'name' => 'Assigned Room or Table ID',        'type' => 'text' ),
			array('key' => 'assigned-start-time',       'name' => 'Assigned Start Time',              'type' => 'text' ),
			array('key' => 'assigned-end-time',         'name' => 'Assigned End Time',                'type' => 'text' ),
			array('key' => 'permit-number',             'name' => 'Permit Number',                    'type' => 'text' ),
			array('key' => 'payment-status',            'name' => 'Payment Status',                   'type' => 'text' ),
			array('key' => 'uuid',                      'name' => 'UUID',                             'type' => 'text' ),
			array('key' => 'qr-data',                   'name' => 'QR Code Data',                     'type' => 'text' ),
			array('key' => 'qr-url',                    'name' => 'QR Code URL',                      'type' => 'text' ),
			array('key' => 'date-created',              'name' => 'Date Created',                     'type' => 'text' ),
			array('key' => 'date-modified',             'name' => 'Date Modified',                    'type' => 'text' ),
			array('key' => 'print-count',               'name' => 'Times Printed',                    'type' => 'int'  ),
			array('key' => 'print-first-time',          'name' => 'First Printed',                    'type' => 'text' ),
			array('key' => 'print-last-time',           'name' => 'Last Printed',                     'type' => 'text' ),
			array('key' => 'checkin-count',             'name' => 'Times Checked In',                 'type' => 'int'  ),
			array('key' => 'checkin-first-time',        'name' => 'First Checked In',                 'type' => 'text' ),
			array('key' => 'checkin-last-time',         'name' => 'Last Checked In',                  'type' => 'text' ),
			array('key' => 'notes',                     'name' => 'Notes',                            'type' => 'text' ),
	);
	$entities = $apdb->list_applicants(null, true, $name_map, $fdb);
	cm_output_csv($columns, $entities, 'badges.csv');
}

cm_admin_head($ctx_name.' CSV Export');
cm_admin_body($ctx_name.' CSV Export');
cm_admin_nav('application-csv-'.$ctx_lc);

echo '<article>';
	echo '<div class="card">';
		echo '<div class="card-content">';
			echo '<h3>'.$ctx_name.' Applications:</h3>';
			echo '<div class="spacing">';
				echo '<form action="csv.php?c='.$ctx_lc.'" method="post">';
					echo '<input type="submit" name="download-applications" value="Download '.$ctx_name.' Applications CSV">';
				echo '</form>';
			echo '</div>';
			echo '<h3>'.$ctx_name.' Badges:</h3>';
			echo '<div class="spacing">';
				echo '<form action="csv.php?c='.$ctx_lc.'" method="post">';
					echo '<input type="submit" name="download-applicants" value="Download '.$ctx_name.' Badges CSV">';
				echo '</form>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
