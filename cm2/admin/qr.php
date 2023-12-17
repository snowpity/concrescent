<?php

require_once __DIR__ .'/admin.php';

cm_admin_head('QR Code Settings');
?><script>
	(function($,window,document){
		$(document).ready(function() {
			switch (window.localStorage.qr) {
				case 'on'  : $('input[type=radio][name=qr][value=on]'  ).prop('checked', true); break;
				case 'off' : $('input[type=radio][name=qr][value=off]' ).prop('checked', true); break;
				case 'auto': $('input[type=radio][name=qr][value=auto]').prop('checked', true); break;
				default    : $('input[type=radio][name=qr][value=null]').prop('checked', true); break;
			}
			$('input[type=radio][name=qr][value=on]'  ).bind('click', function() { window.localStorage.qr = 'on'  ; });
			$('input[type=radio][name=qr][value=off]' ).bind('click', function() { window.localStorage.qr = 'off' ; });
			$('input[type=radio][name=qr][value=auto]').bind('click', function() { window.localStorage.qr = 'auto'; });
			$('input[type=radio][name=qr][value=null]').bind('click', function() { window.localStorage.clear('qr'); });
		});
	})(jQuery,window,document);
</script><?php

cm_admin_body('QR Code Settings');
cm_admin_nav('qr');

echo '<article>';
	echo '<div class="card">';
		echo '<div class="card-content">';
			echo '<div class="spacing">';
				echo '<div><label><input type="radio" name="qr" value="auto">Enabled       </label></div>';
				echo '<div><label><input type="radio" name="qr" value="null">Disabled      </label></div>';
				echo '<div><label><input type="radio" name="qr" value="on"  >Force Enabled </label></div>';
				echo '<div><label><input type="radio" name="qr" value="off" >Force Disabled</label></div>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
