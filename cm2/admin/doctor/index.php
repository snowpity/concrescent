<?php

include 'util.php';

$tests = [
    "https" => 'Checking HTTPS...',
    "phpversion" =>  'Checking PHP version...',
    "config1" =>  'Checking configuration file can be loaded...',
    "config2" =>  'Checking all configuration sections are present...',
    "config3" =>  'Checking database configuration...',
    "config4" =>  'Checking PayPal configuration...',
    "config5" =>  'Checking default administrator user...',
    "database1" =>  'Checking database connection...',
    "database2" =>  'Checking database connection through CONcrescent...',
    "database3" =>  'Checking database date and time...',
    "database4" =>  'Checking database character set...',
    "database5" =>  'Checking user accounts...',
    "curl" =>  'Checking cURL extension...',
    "paypal" =>  'Checking PayPal connection...',
    "mail" =>  'Checking email sending capability...',
    "gd" => 'Checking GD library.',
    "theme" =>  'Checking theme stylesheet...',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= basename(__FILE__, '.php') ?></title>
<style>
  table { border-collapse: collapse; }
  td, th { padding: 4px; border: 1px solid black; }
  td:nth-of-type(1) { width: 6rem; text-align: center; }
	.PASSED { background: green; color: white; }
	.NOTICE { background: yellow; color: black; }
	.FAILED { background: red; color: white; }
	.htmx-request { opacity: 0; }
</style>
<script src="/htmx.min.v204.js"></script>
</head>
<body>
  <table hx-sync="tr:queue all">
      <?php foreach ($tests as $testName => $testExplain) { ?>
        <tr
            id="<?= $testName ?>"
            hx-get="<?= $testName ?>.php"
            hx-trigger="load"
            hx-indicator="#<?= $testName ?> td.level"
        >
        <?php checking($testName, $testExplain) ?>
        </tr>
      <?php } ?>
  </table>
</body>
</html>
