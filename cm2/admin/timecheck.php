<?php

require_once __DIR__ .'/../config/config.php';
require_once __DIR__ .'/admin.php';

cm_admin_check_permission('timecheck', '*');

cm_admin_head('Time Check');
cm_admin_body('Time Check');
cm_admin_nav('timecheck');

echo '<article>';
	echo '<div class="card">';
		echo '<div class="card-content">';
			echo '<div class="spacing">';
				echo '<p>';
					echo '<b>PHP Version:</b> ';
					echo phpversion();
				echo '</p>';
				echo '<p>';
					echo '<b>PHP Magic Quotes:</b> ';
					echo 'OFF';
				echo '</p>';
				echo '<p>';
					echo '<b>PHP Date &amp; Time:</b> ';
					echo date('Y-m-d H:i:s Z');
				echo '</p>';
				echo '<p>';
					echo '<b>PHP Time Zone:</b> ';
					echo date_default_timezone_get();
				echo '</p>';
				echo '<p>';
					$curdatetime = $db->curdatetime();
					echo '<b>MySQL Date &amp; Time:</b> ';
					echo $curdatetime[0].' '.$curdatetime[1];
				echo '</p>';
				echo '<p>';
					$timezone = $db->timezone();
					echo '<b>MySQL Time Zone (Global):</b> ';
					echo $timezone[0];
				echo '</p>';
				echo '<p>';
					echo '<b>MySQL Time Zone (Session):</b> ';
					echo $timezone[1];
				echo '</p>';
				echo '<p>';
					echo '<b>MySQL Time Zone (Configured):</b> ';
					echo $cm_config['database']['timezone'];
				echo '</p>';
				echo '<p><b>MySQL Character Sets:</b></p>';
				$charsets = $db->characterset();
				foreach ($charsets as $k => $v) {
					echo '<p><code>';
					echo htmlspecialchars($k) . ': ';
					echo htmlspecialchars($v);
					echo '</code></p>';
				}
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
