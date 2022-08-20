<?php

error_reporting(0);
header('Content-Type: text/plain');

if(mail('catchall@mailinator.com', 'subject', 'body', 'from@example.invalid')) {
	echo 'OK PHP appears capable of sending email. Please verify with a test registration.';
} else {
	echo 'NG PHP does not appear capable of sending email. Check PHP and SendMail configuration.';
}