<?php

require_once __DIR__ .'/admin.php';

cm_admin_head('Home');
cm_admin_body('Home');
cm_admin_nav('home');

echo '<article>';
	echo '<div class="card cm-home">';
		echo '<div class="card-title">Welcome to CONcrescent</div>';

		echo '<div class="card-content">';
		$now = (new \DateTimeImmutable('now'))->format('r, e \[Y-m-d\TH:i:sP]');
		echo "<p>Server time is : $now</p>";
		echo '<hr>';
		echo '</div>';

		echo '<div class="card-content">';
			$first_group = true;
			foreach ($cm_admin_nav as $group) {
				$first_link = true;
				foreach ($group as $link) {
					if ($link['id'] != 'home' && (
						!isset($link['permission']) || !$link['permission'] ||
						$adb->user_has_permission($admin_user, $link['permission'])
					)) {
						if ($first_link && !$first_group) echo '<hr>';
						$url = get_site_url(false) . $link['href'];
						echo '<h3><a href="' . htmlspecialchars($url) . '">';
						echo htmlspecialchars($link['name']);
						echo '</a></h3>';
						if (isset($link['description']) && $link['description']) {
							echo '<p>' . htmlspecialchars($link['description']) . '</p>';
						}
						$first_link = false;
					}
				}
				if (!$first_link) $first_group = false;
			}
			if ($first_group) {
				echo '<p class="cm-unauthorized">';
				echo 'You do not have permission to view any pages.';
				echo '</p>';
			}
		echo '</div>';
	echo '</div>';
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
