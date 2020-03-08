<?php

require_once dirname(__FILE__).'/lib/util/util.php';

if(!isLegacy())
{
	if(readfile('index.html')) exit(0);
	setLegacyMode(true);
}
header('Location: register/');
