<?php

require_once __DIR__ .'/../../lib/database/attendee.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../../lib/database/misc.php';
require_once __DIR__ . '/../../lib/util/cmexport.php';
require_once __DIR__ .'/../admin.php';

global $twig;

cm_admin_check_permission('attendee-csv', 'attendee-csv');

$atdb = new cm_attendee_db($db);
$name_map = $atdb->get_badge_type_name_map();

$fdb = new cm_forms_db($db, 'attendee');
$questions = $fdb->list_questions();

$miscDb = new cm_misc_db($db);

function extractFromQuestion(string $questionId): string
{
	global $miscDb;

	header('Content-Type: application/json');
	header('Content-Disposition: attachment; filename=export.json');
	header('Pragma: no-cache');
	header('Expires: 0');
	echo json_encode($miscDb->getBadgeTypesFromQuestionAnswer($questionId), JSON_THROW_ON_ERROR);
	die();
}

if (isset($_GET['format'])) {
	$columns = array_merge(
		array(
			array('key' => 'id',                       'name' => 'ID',                              'type' => 'int'  ),
			array('key' => 'id-string',                'name' => 'ID String',                       'type' => 'text' ),
			array('key' => 'first-name',               'name' => 'First Name',                      'type' => 'text' ),
			array('key' => 'last-name',                'name' => 'Last Name',                       'type' => 'text' ),
			array('key' => 'real-name',                'name' => 'Real Name',                       'type' => 'text' ),
			array('key' => 'fandom-name',              'name' => 'Fandom Name',                     'type' => 'text' ),
			array('key' => 'name-on-badge',            'name' => 'Name on Badge',                   'type' => 'text' ),
			array('key' => 'only-name',                'name' => 'Only Name',                       'type' => 'text' ),
			array('key' => 'large-name',               'name' => 'Large Name',                      'type' => 'text' ),
			array('key' => 'small-name',               'name' => 'Small Name',                      'type' => 'text' ),
			array('key' => 'display-name',             'name' => 'Display Name',                    'type' => 'text' ),
			array('key' => 'date-of-birth',            'name' => 'Date of Birth',                   'type' => 'text' ),
			array('key' => 'age',                      'name' => 'Age (Start of Event)',            'type' => 'int'  ),
			array('key' => 'badge-type-id',            'name' => 'Badge Type ID',                   'type' => 'int'  ),
			array('key' => 'badge-type-id-string',     'name' => 'Badge Type ID String',            'type' => 'text' ),
			array('key' => 'badge-type-name',          'name' => 'Badge Type Name',                 'type' => 'text' ),
			array('key' => 'addon-ids',                'name' => 'Addon IDs',                       'type' => 'array'),
			array('key' => 'addon-names',              'name' => 'Addon Names',                     'type' => 'array'),
			array('key' => 'email-address',            'name' => 'Email Address',                   'type' => 'text' ),
			array('key' => 'email-address-subscribed', 'name' => 'Email Address (Subscribed)',      'type' => 'text' ),
			array('key' => 'subscribed',               'name' => 'Subscribed',                      'type' => 'bool' ),
			array('key' => 'unsubscribe-link',         'name' => 'Unsubscribe Link',                'type' => 'text' ),
			array('key' => 'phone-number',             'name' => 'Phone Number',                    'type' => 'text' ),
			array('key' => 'address-1',                'name' => 'Street Address (First Line)',     'type' => 'text' ),
			array('key' => 'address-2',                'name' => 'Street Address (Second Line)',    'type' => 'text' ),
			array('key' => 'address',                  'name' => 'Street Address',                  'type' => 'text' ),
			array('key' => 'city',                     'name' => 'City',                            'type' => 'text' ),
			array('key' => 'state',                    'name' => 'State or Province',               'type' => 'text' ),
			array('key' => 'zip-code',                 'name' => 'ZIP or Postal Code',              'type' => 'text' ),
			array('key' => 'csz',                      'name' => 'City, State, ZIP',                'type' => 'text' ),
			array('key' => 'country',                  'name' => 'Country',                         'type' => 'text' ),
			array('key' => 'address-full',             'name' => 'Street Address (Full)',           'type' => 'text' ),
		),
		cm_form_questions_to_csv_columns($questions),
		array(
			array('key' => 'ice-name',                 'name' => 'Emergency Contact Name',          'type' => 'text' ),
			array('key' => 'ice-relationship',         'name' => 'Emergency Contact Relationship',  'type' => 'text' ),
			array('key' => 'ice-email-address',        'name' => 'Emergency Contact Email Address', 'type' => 'text' ),
			array('key' => 'ice-phone-number',         'name' => 'Emergency Contact Phone Number',  'type' => 'text' ),
			array('key' => 'payment-status',           'name' => 'Payment Status',                  'type' => 'text' ),
			array('key' => 'payment-badge-price',      'name' => 'Payment Badge Price',             'type' => 'price'),
			array('key' => 'payment-promo-code',       'name' => 'Payment Promo Code',              'type' => 'text' ),
			array('key' => 'payment-promo-price',      'name' => 'Payment Promo Price',             'type' => 'price'),
			array('key' => 'payment-group-uuid',       'name' => 'Payment Group UUID',              'type' => 'text' ),
			array('key' => 'payment-type',             'name' => 'Payment Type',                    'type' => 'text' ),
			array('key' => 'payment-txn-id',           'name' => 'Payment Transaction ID',          'type' => 'text' ),
			array('key' => 'payment-txn-amt',          'name' => 'Payment Transaction Amount',      'type' => 'price'),
			array('key' => 'payment-date',             'name' => 'Payment Date',                    'type' => 'text' ),
			array('key' => 'payment-details',          'name' => 'Payment Details',                 'type' => 'text' ),
			array('key' => 'review-link',              'name' => 'Review Order Link',               'type' => 'text' ),
			array('key' => 'uuid',                     'name' => 'UUID',                            'type' => 'text' ),
			array('key' => 'qr-data',                  'name' => 'QR Code Data',                    'type' => 'text' ),
			array('key' => 'qr-url',                   'name' => 'QR Code URL',                     'type' => 'text' ),
			array('key' => 'date-created',             'name' => 'Date Created',                    'type' => 'text' ),
			array('key' => 'date-modified',            'name' => 'Date Modified',                   'type' => 'text' ),
			array('key' => 'print-count',              'name' => 'Times Printed',                   'type' => 'int'  ),
			array('key' => 'print-first-time',         'name' => 'First Printed',                   'type' => 'text' ),
			array('key' => 'print-last-time',          'name' => 'Last Printed',                    'type' => 'text' ),
			array('key' => 'checkin-count',            'name' => 'Times Checked In',                'type' => 'int'  ),
			array('key' => 'checkin-first-time',       'name' => 'First Checked In',                'type' => 'text' ),
			array('key' => 'checkin-last-time',        'name' => 'Last Checked In',                 'type' => 'text' ),
			array('key' => 'notes',                    'name' => 'Notes',                           'type' => 'text' ),
		)
	);

	$entities = $atdb->list_attendees(null, null, $name_map, $fdb);

	match($_GET['format']) {
		'CSV' => cm_output_csv($columns, $entities, 'attendees.csv'),
		'JSON' => cm_output_json($columns, $entities, 'attendees.json'),
		'EXTRACT' => extractFromQuestion($_GET['question-id']),
		default => die('Unsupported format.'),
	};
}

echo $twig->render('pages/admin/attendee/export.twig', [
	'questions' => $questions,
]);
