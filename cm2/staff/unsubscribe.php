<?php

require_once __DIR__ .'/staff.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$found = $email ? $sdb->unsubscribe_email_address($email) : false;

cm_app_head('Unsubscribe');
cm_app_body('Unsubscribe');
echo '<article>';

echo '<form action="unsubscribe.php" method="post" class="card">';
	echo '<div class="card-title">Unsubscribe from Promotional Emails</div>';
	echo '<div class="card-content">';
		if ($email) {
			if ($found) {
				echo '<p class="cm-success-box">';
					echo 'You have been successfully unsubscribed.<br>';
					echo '<small>(Guru Meditation Number ' . $found . ')</small>';
				echo '</p>';
			} else {
				echo '<p class="cm-error-box">';
					echo 'Could not find this email address. ';
					echo 'You may have already been unsubscribed.';
				echo '</p>';
			}
		} else {
			$email = isset($_GET['email']) ? trim($_GET['email']) : '';
		}
		echo '<p>';
			echo 'Enter Email Address:';
			echo '&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '<input type="email" name="email"';
			if ($email) echo ' value="' . htmlspecialchars($email) . '"';
			echo '>';
		echo '</p>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<input type="submit" name="submit" value="Unsubscribe" class="register-button">';
	echo '</div>';
echo '</form>';

echo '</article>';
cm_app_tail();
