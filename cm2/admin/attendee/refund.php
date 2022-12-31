<?php

require_once dirname(__FILE__).'/../../lib/database/attendee.php';
require_once dirname(__FILE__).'/../../lib/database/forms.php';
require_once dirname(__FILE__).'/../../lib/database/mail.php';
require_once dirname(__FILE__).'/../../lib/util/util.php';
require_once dirname(__FILE__).'/../../lib/util/res.php';
require_once dirname(__FILE__).'/../../lib/util/cmforms.php';
require_once dirname(__FILE__).'/../../lib/util/slack.php';
require_once dirname(__FILE__).'/../../lib/util/paypal.php';
require_once dirname(__FILE__).'/../admin.php';

cm_admin_check_permission('attendees', array('||', 'attendees-edit', 'attendees-refund'));
$can_edit = $adb->user_has_permission($admin_user, 'attendees-refund');


$atdb = new cm_attendee_db($db);
$new = !isset($_GET['id']);
$id = $new ? -1 : (int)$_GET['id'];
$item = $new ? array() : $atdb->get_attendee($id, false);
$submitted = isset($_POST['payment-txn-id']);

$name = isset($item['display-name']) ? $item['display-name'] : null;

$transactions = transaction_details_listTransactions($item['payment-details']);

if(count($transactions) == 0)
{

      cm_admin_head('Refund Attendee');
      cm_admin_body('Refund Attendee');
      cm_admin_nav('attendees');

      echo '<article>';
      echo '<div class="card ">';
      if ($name) {
        echo '<div class="card-title">' . htmlspecialchars($name) . '</div>';
      }
      echo '<div class="card-content">No eligible transactions to refund... <a href="edit.php?id=' . $id .'">Click here</a> to return to Edit screen.</div>';
      echo '</div>';
      echo '</article>';
      cm_admin_tail();
      exit(0);
}

if($submitted){
  //Get the transaction
  $tx = $transactions[$_POST['payment-txn-id']];
  //Pre-check: Is the transaction approved?
  if($tx['payment_status'] != 'approved' || is_null($tx))
  {
    die('Can\'t let you do that, Starfox!');
  }
  $keep_payment_status = isset($_POST['keep_status']) &&  isset($_POST['keep_status']) == 1;
	$paypal = new cm_paypal();
	$token = $paypal->get_token();

  $refund_result = $paypal->execute_refund($tx['payment_saleID'],$tx['invoice_number'], $_POST['refund-amt'], $_POST['refund-note']);
  //Update for the notes
  $newDetails = transaction_details_update($item['payment-details'],$tx['payment-txn-id'].":refund",$refund_result);
  //Check if we did it
  if(isset($refund_result['state']))
  {
    //Worked just fine
    $affected_payment = $paypal->retrieve_payment($tx['payment_payID']);
    //Update the payment details for the payment
    $newDetails = transaction_details_update($newDetails,$tx['payment-txn-id'],$affected_payment);
  }
  //Save changes
  $success = $atdb->update_payment_status($item['id'], $keep_payment_status ? $item['payment-status'] : 'Refunded', 'PayPal', $item['payment-txn-id'], $newDetails);
  //Update
  $newnote = $item['notes'] . "\r\nRefunded " .$_POST['refund-amt'] . ' by ' . $admin_user['name'] . ' on ' . gmdate("Y-m-d\TH:i:s\Z") . ($_POST['refund-note'] != '' ? ' with note: ' . $_POST['refund-note'] : '');
  $successnote = $atdb->update_attendee_notes($item['id'],$newnote);
  if($success)
  {

    cm_admin_head('Refund Attendee');
    cm_admin_body('Refund Attendee');
    cm_admin_nav('attendees');

    echo '<article>';
    echo '<div class="card ">';
    if ($name) {
      echo '<div class="card-title">' . htmlspecialchars($name) . '</div>';
    }
    echo '<div class="card-content">Refund complete. <a href="edit.php?id=' . $id .'">Click here</a> to return to Edit screen.</div>';
    echo '</div>';
    echo '</article>';

    cm_admin_tail();
    exit(0);
  }
}

//Refund form
cm_admin_head('Refund Attendee');
cm_admin_body('Refund Attendee');
cm_admin_nav('attendees');

echo '<article>';

$url = 'refund.php?id=' . $id;
echo '<form action="' . $url . '" method="post" class="card cm-reg-edit"  onsubmit="submit.disabled = true; submit.value=\'Sending, please wait...\';return true;">';

if ($name) {
  echo '<div class="card-title">' . htmlspecialchars($name) . '</div>';
}

echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';

echo '<tr>';
	echo '<th><label for="payment-txn-id">Transaction</label></th>';
	echo '<td><select id="payment-txn-id" name="payment-txn-id">';
  foreach ($transactions as $key => $value) {
    echo '<option value="' .$value["payment-txn-id"] .'" data-amt="' .$value['payment_txn_amt']. '">' .$value['payment_txn_amt']. ' : ' . $value['payment_saleID']. ' : ' . $value['payment_status']. '</option>';
  }
  echo '</td>';
echo '</tr>';

echo '<tr>';
	echo '<th><label for="refund-amt">Refund Amount</label></th>';
	echo '<td><input type="number" id="refund-amt" name="refund-amt" step="0.01"  value="' . reset($transactions)['payment_txn_amt'] .'"/>';
  echo '</td>';
echo '</tr>';

echo '<tr>';
	echo '<th><label for="refund-note">Refund Note</label></th>';
	echo '<td><input type="text" id="refund-note" name="refund-note"/>';
  echo '</td>';
echo '</tr>';

echo '<tr>';
echo '<td></td><td><label>';
  echo '<input type="checkbox" name="keep_status" value="1">';
  echo 'Keep current payment status (' . $item['payment-status'] . ').';
echo '</label></td>';
echo '</tr>';

echo '</table>';



echo '<div class="card-buttons">';
  echo '<input type="submit" name="submit" value="Request Refund">';
echo '</div>';
echo '</form>';
echo '</article>';

cm_admin_tail();
