<?php

# Fail silently if the config file isn't available at the target.
# This indirection is useful for running the application in all kinds of setup even without Docker.
# You can also override this current file.
/** @phpstan-ignore include.fileNotFound */
include('/srv/host/config.php');

if(!isset($cm_config)) {
	echo "Could not find the config file where it should be. Check your installation again.";
    die();
}

