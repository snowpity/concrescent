<?php

/* Time zone PHP should use for date calculations (e.g. when badges are available). */
date_default_timezone_set('America/Los_Angeles');

/* This is the default configuration for CONcrescent. Replace all values in this file. */
return array(

    /* Database Configuration. Currently only MySQL 7+ (and compatible) is supported */
    'database' => array(

        /* Host name or IP address of the MySQL server. Typically 'localhost' or '127.0.0.1'. */
        'host' => 'localhost',
        'username' => 'cm_user',
        'password' => 'cm_pass',

        /* Name of the MySQL database to use for this application. */
        'database' => 'cm3_db',
        'prefix' => 'cm_',
        /* Time zone MySQL should use for date calculations (e.g. when badges are available). */
        'timezone' => 'SYSTEM',
    ),
    'environment' => array(
      //If not installed in the web root, specify it here.
      //No trailing slash!
      'base_path' => '/concrescent/cm3/backend',
      //For the purposes of generating links back to ConCrescent Frontend, where is the front-end hosted?
      //Include trailing slash!
      'frontend_host' => 'https://tsaukpaetra.com/concrescent/cm3/frontend/dist/',
      //If the frontend is in Hash Mode
      'frontend_isHashMode' => true,
      //Should responses be GZipped?
      'use_gzip' => true,
      //Secret key to sign tokens with. Must be exactly 32 bytes!
      //'token_secret' => hex2bin("f349e1808732b6c0bc545b1ee8926e69a55478d6985af34c3e99bfa45e1f64d8")
      'token_secret' => 'AReallySecureKeyThatNobodyKnows!',
      //If someone who signed in doesn't load in the site (and get their session renewed)
      //How long (in seconds) until we log them out?
      'token_life' => 345600 //Four days is exceptionally generous, adjust to your needs
    ),
    'error' => array(

      // Should be set to false for the production environment.
      //If true API requests may contain HTML!
      'display_error_details' => true,
      // Should be set to false for the test environment
      'log_errors' => true,
      // Display error details (stack trace) in error log
      'log_error_details' => true,
    ),
    'logger' => array(
        //Comment out to stop file logging
        'path' => dirname(__FILE__) .'/logs',
        'level' => \Monolog\Logger::INFO,
    ),
    'mailer' => array(
        //Can be:
        //Mail: Uses PHP's mail() function (Default)
        //SMTP: Provide credentials
        //Sendmail: uses PHP's configured sendmail
        //Gmail: Uses App token to send mail via Gmail
        'mode' => 'SMTP',

        //Credentials, Username/password is ClientID/ClientSecret with oauth/gmail
        //Leave blank for no auth
        'Host' => 'mail.example.com',
        'Port' => 465,
        'Username' => 'yoursmtpuser@example.com',
        'Password' => 'therealpassword',

        'defaultFrom' => 'yoursmtpuser@example.com'
    ),
    'payments' => array(
        'Cash' => array(
            //set to false if we don't even want the option to pay with cash
            //Note that if enabled, attendees will be unable to complete their order online
            //This means that they might not get get a limited-number badge
            //
            //Special exception is taken for the 'cash' payment type, in that
            //it is only available for badges with the 'Payable on site' tickbox checked.
            'allowed'      => true,
            'SalesTax'     => 0.085,
        ),
        'PayPal' => array(
            'ClientID'     => 'YourPayPalClientID',
            'ClientSecret' => 'YouPayPalClientSecret',
            'CurrencyType' => 'USD',
            'SalesTax'     => 0.085,
            'sandbox'     => true,
            //Note that redirect URL will be of the form:
            //{frontend_host}#/CompletePayment
            //{frontend_host}#/cart?checkout=confirm&cart_uuid=1234567
        )
    )
);
