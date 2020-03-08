<?php
require_once dirname(__FILE__).'/../lib/util/util.php';
require_once dirname(__FILE__).'/../lib/database/mail.php';
require_once dirname(__FILE__).'/../register/register.php';

//Assume we got something
$json = json_decode(file_get_contents("php://input"), true);

$gid = isset($json['gid']) ? trim($json['gid']) : null;
$tid = isset($json['tid']) ? trim($json['tid']) : null;

if ($gid && $tid) {
$items_raw = $atdb->list_attendees($gid, $tid, $name_map, $fdb);

//Do some filtering... Attendees don't need all that data!
$keepkeys_root = array("id", "qr-data", "id-string","uuid",
 "first-name",
 "last-name",
 "fandom-name",
 "name-on-badge",
 "date-of-birth",
 "badge-type-id",
 "email-address",
 "subscribed",
 "phone-number",
 "address-1",
 "address-2",
 "city",
 "state",
 "zip-code",
 "country",
 "ice-name",
 "ice-relationship",
 "ice-email-address",
 "ice-phone-number",
 "payment-status",
 "payment-promo-code",
 "payment-promo-type",
 "payment-promo-amount",
 "addon-ids",
 "form-answers",
 "badge-type-name"
);

$keepkeys_addons = array("id","addon-id",
"name",
"description"
);
$items = array();
foreach ($items_raw as $badgeix => $badge) {
  $items[$badgeix] = array_intersect_key($badge, array_flip($keepkeys_root));
  //Add addons display
  if(isset($badge['addons']))
  foreach ($badge['addons'] as $addonix => $addon) {
    $items[$badgeix]['addons'][$addonix] = array_intersect_key($addon, array_flip($keepkeys_addons));
  }
}


echo json_encode($items);
} else if (isset($json['email'])) {

    $mdb = new cm_mail_db($db);
    $reviewlinks = $atdb->retrieve_attendee_reviewlinks($json['email']);
    //echo json_encode($reviewlinks);
    $template = $mdb->get_mail_template('attendee-retrieve');
    foreach ($reviewlinks as $key => $item) {
      $mdb->send_mail($json['email'], $template, array('review-link' => $item));
    }

} else {
  echo json_encode(array());
}
