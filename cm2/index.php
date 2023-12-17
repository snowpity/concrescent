<?php

require_once './lib/util/util.php';

if(!isLegacy())
{
	if(readfile('index.html')) exit(0);
	setLegacyMode(true);
}
header('Location: /register');
