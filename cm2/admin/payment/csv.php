<?php

require_once __DIR__ .'/../../lib/database/payment.php';
require_once __DIR__ .'/../../lib/util/cmcsv.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('payment-csv', 'payment-csv');

$pdb = new cm_payment_db($db);

if (isset($_POST['download-payments'])) {
	$columns = array(
		array('key' => 'id',                  'name' => 'ID',                         'type' => 'int'  ),
		array('key' => 'id-string',           'name' => 'ID String',                  'type' => 'text' ),
		array('key' => 'requested-by',        'name' => 'Requested By',               'type' => 'text' ),
		array('key' => 'first-name',          'name' => 'First Name',                 'type' => 'text' ),
		array('key' => 'last-name',           'name' => 'Last Name',                  'type' => 'text' ),
		array('key' => 'real-name',           'name' => 'Real Name',                  'type' => 'text' ),
		array('key' => 'email-address',       'name' => 'Email Address',              'type' => 'text' ),
		array('key' => 'mail-template',       'name' => 'Form Letter',                'type' => 'text' ),
		array('key' => 'payment-name',        'name' => 'Payment For',                'type' => 'text' ),
		array('key' => 'payment-description', 'name' => 'Description',                'type' => 'text' ),
		array('key' => 'payment-price',       'name' => 'Requested Amount',           'type' => 'price'),
		array('key' => 'payment-status',      'name' => 'Payment Status',             'type' => 'text' ),
		array('key' => 'payment-type',        'name' => 'Payment Type',               'type' => 'text' ),
		array('key' => 'payment-txn-id',      'name' => 'Payment Transaction ID',     'type' => 'text' ),
		array('key' => 'payment-txn-amt',     'name' => 'Payment Transaction Amount', 'type' => 'price'),
		array('key' => 'payment-date',        'name' => 'Payment Date',               'type' => 'text' ),
		array('key' => 'payment-details',     'name' => 'Payment Details',            'type' => 'text' ),
		array('key' => 'review-link',         'name' => 'Review Order Link',          'type' => 'text' ),
		array('key' => 'uuid',                'name' => 'UUID',                       'type' => 'text' ),
		array('key' => 'qr-data',             'name' => 'QR Code Data',               'type' => 'text' ),
		array('key' => 'qr-url',              'name' => 'QR Code URL',                'type' => 'text' ),
		array('key' => 'date-created',        'name' => 'Date Created',               'type' => 'text' ),
		array('key' => 'date-modified',       'name' => 'Date Modified',              'type' => 'text' ),
	);
	$entities = $pdb->list_payments();
	cm_output_csv($columns, $entities, 'payments.csv');
}

cm_admin_head('Payment Request CSV Export');
cm_admin_body('Payment Request CSV Export');
cm_admin_nav('payment-csv');

echo '<article>';
	echo '<div class="card">';
		echo '<div class="card-content">';
			echo '<h3>Payment Requests:</h3>';
			echo '<div class="spacing">';
				echo '<form action="csv.php" method="post">';
					echo '<input type="submit" name="download-payments" value="Download Payment Requests CSV">';
				echo '</form>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
