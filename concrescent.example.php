<?php

/* Time zone PHP should use for date calculations (e.g. when badges are available). */
date_default_timezone_set(getenv("TZ"));

/* This is the default configuration for CONcrescent. Replace all values in this file. */
$cm_config = [

	/* Override the site's base URL.
	   This is required if due to proxy shenanigans
	   the actual server URL cannot be properly determined.
	   Otherwise, this should be left blank. */
	'site-override' => '',

	/* Database Configuration */
	'database' => [
		/* Host name or IP address of the MySQL server. Typically 'localhost' or '127.0.0.1'. In a docker-compose setup it should be 'mysql'. */
		'host' => 'mysql',
		'username' => getenv('MYSQL_PASSWORD'),
		'password' => getenv('MYSQL_USER'),
		'database' => getenv('MYSQL_DATABASE'),
		/* Time zone MySQL should use for date calculations (e.g. when badges are available). */
		'timezone' => 'SYSTEM',
	],

	/* PayPal Configuration, REST API credentials */
	'paypal' => [
		'api_url' => 'api.sandbox.paypal.com',
		'client_id' => '',
		'secret' => '',
		'currency' => '',
	],

    'payment' => [
        'sales_tax' => 0.065, /// 6.5%
    ],

    'extra_features' => [
        'sponsors' => [
            'nameCredit' => null,/// Id of the question holding the credit name.
            'publishableCredit' => null,/// Id of the question telling if it can be published.
        ],
    ],

    'cloudflare' => [
        'bearer_token' => null,
        'purge' => [
            'zone_id' => null,
            'sponsor_files' => null,
			'schedule_files' => null,
        ],
    ],

    'logging' => [
        'log_dir' => '/var/www/log',
    ],

	/* Slack Integration Configuration */
	'slack' => [
		/* Slack notification hooks. */
		'hook_url' => [
			/* Notification hook for blacklisted attendee registrations. */
			'attendee-blacklisted' => '',
			/* Notification hooks for blacklisted applications. */
			'application-blacklisted' => [
				'B' => '', // Vendors
				'E' => '', // Panels
			],
			/* Notification hooks for application submission. */
			'application-submitted' => [
				'B' => '', // Vendors
				'E' => '', // Panels
			],
			/* Notification hooks for application approval. */
			'application-accepted' => [
				'B' => '', // Vendors
				'E' => '', // Panels
			],
			/* Notification hook for blacklisted staff applications. */
			'staff-blacklisted' => '',
			/* Notification hook for staff application submission. */
			'staff-submitted' => '',
			/* Notification hook for staff application approval. */
			'staff-accepted' => '',
		],
	],

	/* Event Configuration */
	'event' => [
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
	],

	/* Application Configuration */
	'application_types' => [
		/* Vendors */
		'B' => [
			'nav_prefix' => 'Vendor',
			'assignment_term' => ['Table', 'Tables'],
			'business_name_term' => 'Business Name',
			'business_name_text' => 'The name of the business, organization, group, or individual selling or tabling.',
			'application_name_term' => 'Table Name',
			'application_name_text' => 'The name of the table. This is the name that appears publicly.'
		],
		/* Panels */
		'E' => [
			'nav_prefix' => 'Panel',
			'assignment_term' => ['Time Slot', 'Time Slots'],
			'business_name_term' => 'Presenter Name',
			'business_name_text' => 'The name of the business, organization, group, or individual presenting the panel.',
			'application_name_term' => 'Panel Name',
			'application_name_text' => 'The name of the panel. This is the name that appears publicly.'
		],
	],

	/* Review Mode Configuration */
	'review_mode' => [
		/* Show street address in review mode. */
		'show_address' => true,
		/* Show emergency contact information in review mode. */
		'show_ice' => true,
	],

	/* Badge Printing Configuration */
	'badge_printing' => [
		/* The size of the image to be sent to the badge printer. */
		'width' => '324px',
		'height' => '204px',
		'vertical' => false,
		/* Any external stylesheets to load. */
		'stylesheet' => [],
		/* URL to receive a POST request when a badge is printed.
		   This happens in place of sending a job to the printer. */
		'post_url' => '',
	],

	/* Default Admin User Configuration, if no admin users exist at all.*/
	'default_admin' => [
		'name' => 'Administrator',
		'username' => 'admin',
		'password' => 'admin',
	],

	/* Theme Configuration */
	'theme' => [
		/* Location of the theme directory. */
		'location' => 'themes/luna',
	],
];
