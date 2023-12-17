<?php

require_once __DIR__ .'/config.php';
require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/admin.php';
require_once __DIR__ .'/../lib/database/application.php';
require_once __DIR__ .'/../lib/database/attendee.php';
require_once __DIR__ .'/../lib/database/badge-artwork.php';
require_once __DIR__ .'/../lib/database/badge-holder.php';
require_once __DIR__ .'/../lib/database/forms.php';
require_once __DIR__ .'/../lib/database/mail.php';
require_once __DIR__ .'/../lib/database/misc.php';
require_once __DIR__ .'/../lib/database/payment.php';
require_once __DIR__ .'/../lib/database/staff.php';

$db = new cm_db();
new cm_admin_db($db);
foreach ($cm_config['application_types'] as $ctx => $x) {
	new cm_application_db($db, $ctx);
}
new cm_attendee_db($db);
new cm_badge_artwork_db($db);
new cm_badge_holder_db($db);
new cm_forms_db($db, null);
new cm_mail_db($db);
new cm_misc_db($db);
new cm_payment_db($db);
new cm_staff_db($db);
