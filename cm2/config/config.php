<?php

# Fail silently if the config file isn't available on the mounting point.
# This indirection is useful for an easier time running the legacy application on Docker.
# You can safely override thi entire file with the final config file when running a production release.shuld
include('/srv/host/config.php');

if(!isset($cm_config)) {
	echo "Could not find the config file where it should be. Check your installation again.";
    die();
}

