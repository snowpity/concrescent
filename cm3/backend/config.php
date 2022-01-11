<?php

/* Time zone PHP should use for date calculations (e.g. when badges are available). */
date_default_timezone_set('America/Los_Angeles');

/* This is the default configuration for CONcrescent. Replace all values in this file. */
return (object) array(

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
);
