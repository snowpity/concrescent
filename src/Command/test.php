<?php

use App\Config\ConfigurationMapper;
use App\Kernel;

require_once __DIR__.'/../../vendor/autoload.php';

try {
    $kernel = new Kernel(
        new ConfigurationMapper()
    );
} catch(Throwable $e) {

}

echo 'Kernel booted correctly. Debug mode : '. ($kernel->isAppDebug ? 'enabled' : 'disabled');
echo "\n";
