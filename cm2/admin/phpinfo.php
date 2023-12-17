<?php

if (!(getenv('APP_DEBUG') === 'true')) {
	header('Location: ../');
	die();
}

phpinfo();
