<?php

/* PHP error reporting. In production, this should be 0. In testing, this may be -1. */
error_reporting(0);

/* Time zone PHP should use for date calculations (e.g. when badges are available). */
date_default_timezone_set('America/Los_Angeles');

/* If magic quotes is on, undo the evil things PHP has done. */
require_once dirname(__FILE__).'/../lib/util/dontbeevil.php';

/* This is the default configuration for CONcrescent. Replace all values in this file. */
$cm_config = array(

	/* Database Configuration */
	'database' => array(

		/* Host name or IP address of the MySQL server. Typically 'localhost' or '127.0.0.1'. */
		'host' => 'localhost',

		/* MySQL user name. */
		'username' => 'cm_user',

		/* MySQL user password. */
		'password' => 'cm_pass',

		/* Name of the MySQL database to use for this application. */
		'database' => 'cm_db',

		/* A string to prepend to MySQL table names for this application. */
		'prefix' => 'cm_',

		/* Time zone MySQL should use for date calculations (e.g. when badges are available). */
		'timezone' => 'SYSTEM',

	),

	/* PayPal Configuration */
	'paypal' => array(

		/* URL of the PayPal API server.
		   In production, this is 'api.paypal.com'.
		   In testing, this is 'api.sandbox.paypal.com'. */
		'api_url' => 'api.sandbox.paypal.com',

		/* The Client ID from your PayPal app's REST API credentials. */
		'client_id' => '',

		/* The Secret from your PayPal app's REST API credentials. */
		'secret' => '',

		/* The currency code for all monetary amounts. */
		'currency' => 'USD',

	),

	/* Slack Integration Configuration */
	'slack' => array(

		/* Slack notification hooks. */
		'hook_url' => array(

			/* Notification hook for blacklisted attendee registrations. */
			'attendee-blacklisted' => '',

			/* Notification hooks for blacklisted applications. */
			'application-blacklisted' => array(
				/* Vendors */ 'B' => '',
				/* Panels  */ 'E' => '',
				/* Guests  */ 'G' => '',
				/* Press   */ 'M' => '',
			),

			/* Notification hooks for application submission. */
			'application-submitted' => array(
				/* Vendors */ 'B' => '',
				/* Panels  */ 'E' => '',
				/* Guests  */ 'G' => '',
				/* Press   */ 'M' => '',
			),

			/* Notification hooks for application approval. */
			'application-accepted' => array(
				/* Vendors */ 'B' => '',
				/* Panels  */ 'E' => '',
				/* Guests  */ 'G' => '',
				/* Press   */ 'M' => '',
			),

			/* Notification hook for blacklisted staff applications. */
			'staff-blacklisted' => '',

			/* Notification hook for staff application submission. */
			'staff-submitted' => '',

			/* Notification hook for staff application approval. */
			'staff-accepted' => '',

		),

	),

	/* Event Configuration */
	'event' => array(

		/* The name of the event. */
		'name' => 'CONcrescent Test Event',

		/* The first date requiring availability of staff members, in YYYY-MM-DD format. */
		'staff_start_date' => '2015-12-31',

		/* The first date of the event, in YYYY-MM-DD format. */
		'start_date' => '2015-12-31',

		/* The last date of the event, in YYYY-MM-DD format. */
		'end_date' => '2015-12-31',

		/* The last date requiring availability of staff members, in YYYY-MM-DD format. */
		'staff_end_date' => '2015-12-31',

	),

	/* Application Configuration */
	'application_types' => array(

		/* Vendors */
		'B' => array(
			'nav_prefix' => 'Vendor',
			'assignment_term' => array('Table', 'Tables'),
			'business_name_term' => 'Business Name',
			'business_name_text' => 'The name of the business, organization, group, or individual selling or tabling.',
			'application_name_term' => 'Table Name',
			'application_name_text' => 'The name of the table. This is the name that appears publicly.'
		),

		/* Panels */
		'E' => array(
			'nav_prefix' => 'Panel',
			'assignment_term' => array('Time Slot', 'Time Slots'),
			'business_name_term' => 'Presenter Name',
			'business_name_text' => 'The name of the business, organization, group, or individual presenting the panel.',
			'application_name_term' => 'Panel Name',
			'application_name_text' => 'The name of the panel. This is the name that appears publicly.'
		),

		/* Guests */
		'G' => array(
			'nav_prefix' => 'Guest',
			'assignment_term' => array('Time Slot', 'Time Slots'),
			'business_name_term' => 'Business Name',
			'business_name_text' => 'The name of the business, organization, group, or individual representing the guest.',
			'application_name_term' => 'Guest Name',
			'application_name_text' => 'The name by which the guest is known. This is the name that appears publicly.'
		),

		/* Press */
		'M' => array(
			'nav_prefix' => 'Press',
			'assignment_term' => array('Time Slot', 'Time Slots'),
			'business_name_term' => 'Business Name',
			'business_name_text' => 'The name of the business, organization, group, or individual who owns the publication.',
			'application_name_term' => 'Publication Name',
			'application_name_text' => 'The name of the publication. This is the name that appears publicly.'
		),

	),

	/* Review Mode Configuration */
	'review_mode' => array(

		/* Show street address in review mode. */
		'show_address' => true,

		/* Show emergency contact information in review mode. */
		'show_ice' => true,

	),

	/* Badge Printing Configuration */
	'badge_printing' => array(

		/* The size of the image to be sent to the badge printer. */
		'width' => '324px',
		'height' => '204px',
		'vertical' => false,

		/* Any external stylesheets to load. */
		'stylesheet' => array(),

		/* URL to receive a POST request when a badge is printed.
		   This happens in place of sending a job to the printer. */
		'post_url' => '',

	),

	/* Default Admin User Configuration */
	'default_admin' => array(

		/* Real name of the default admin user, which is created if no users exist. */
		'name' => 'Administrator',

		/* User name of the default admin user, which is created if no users exist. */
		'username' => '',

		/* Password for the default admin user, which is created if no users exist. */
		'password' => '',

	),

	/* Theme Configuration */
	'theme' => array(

		/* Location of the theme directory. */
		'location' => 'themes/luna',

	),

);