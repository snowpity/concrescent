<?php

require_once dirname(__FILE__).'/../lib/util/util.php';
require_once dirname(__FILE__).'/../lib/util/cmforms.php';
require_once dirname(__FILE__).'/../register/register.php';

//Assume we got something
$json = json_decode(file_get_contents("php://input"), true);

//Check that the JSON was valid-ish
if(json_last_error() && !isset($_GET['action']))
{
  http_response_code(400);
  exit(0);
}
header('Content-Type: application/json');
//If there is a badges object, replace the cart with whatever it is.
//This resets the session!
if(isset($json['badges']))
{
  cm_reg_cart_destroy(false);
  $errors = array();
  foreach ($json['badges'] as $key => $badge) {
    $newitem = array();
    $errors[isset($badge['index']) ? $badge['index'] : ($key .'')] = cm_reg_item_update_from_post($newitem,$badge);
    //Ensure there is an index associated
    $newitem['index'] = isset($badge['index']) ? $badge['index'] : ($key .'');
    cm_reg_cart_add($newitem);
  }
  //Count up the errors
  $errorCount = 0;
  foreach($errors as $errorsection)
  {
    $errorCount += count($errorsection);
  }
  //If there are errors, report back
  if($errorCount > 0)
  {
    http_response_code(400);
    echo json_encode(array('errors' => $errors));
    exit(0);
  }

}

//You made it this far. Did you want to do something with the cart?
if(isset($json['action']))
{
  switch ($json['action']) {
    case 'get':
      //Do nothing, we'll spit out the status anyways
      break;
    case 'checkout':
      // Verify cart and attempt checkout
      $errors = cm_reg_cart_verify_availability($json['payment_method']);
      if($errors) {
        http_response_code(400);
        echo json_encode(array('errors' => $errors));
        exit(0);
      }
      //Looks good!
    	$_SESSION['payment_method'] = $json['payment_method'];
      cm_reg_cart_set_state('ready');
      break;
    case 'applypromo':
      $error = cm_reg_apply_promo_code($json['code']);
      if($error) {
        http_response_code(400);
        echo json_encode(array('errors' => array('promo' => $error)));
        exit(0);
      }
      cm_reg_cart_set_state('promoapplied');

      break;
    default:
      // code...
      break;
  }
  echo json_encode(array('cart' => $_SESSION['cart'], 'state' => $_SESSION['cart_state'] ?? "" ));
  exit(0);
}


switch ($_GET['action']) {
  case 'checkout':
    unset($_GET['action']);
    require dirname(__FILE__).'/../register/checkout.php';
}
