<?php

require_once 'util.php';
error_reporting(0);

if(mail('catchall@mailinator.com', 'subject', 'body')) {
    passed('mail', 'PHP appears capable of sending email. Please verify with a test registration.');
} else {
    failed('mail', 'PHP does not appear capable of sending email. Check PHP and SendMail configuration.');
}
