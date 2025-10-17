<?php

use App\Config\ConfigurationMapper;
use App\Lib\Util\cm_paypal;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Symfony\Component\Cache\Adapter\NullAdapter;

require_once 'util.php';
require_once __DIR__ .'/../../../config/concrescent.php';
error_reporting(0);

$success = false;

function print_success() {
	if ($GLOBALS['success']) {
        passed('paypal', 'Successfully connected to PayPal and received token.');
	} else {
        failed('paypal', 'Could not connect to PayPal or could not receive token. Check PayPal configuration and make sure OpenSSL is up to date.');
    }
}

register_shutdown_function(print_success(...));

$config = new ConfigurationMapper()->mapToConfiguration($cm_config);

$logger = new Logger('paypal');
$debugStdoutHandler = new StreamHandler(
    'php://stdout',
    Level::Debug
);
$logger->pushHandler($debugStdoutHandler);
$logger->pushProcessor(new PsrLogMessageProcessor());

$paypal = @new cm_paypal(
    $config->paypal,
    $config->event,
    new NullAdapter(),
    $logger
);


# if query "purge" exists, prune paypal token
if (isset($_GET['purge'])) {
    $paypal->pruneToken();
}


$token = @$paypal->token->accessToken;
$success = (bool)$token;
