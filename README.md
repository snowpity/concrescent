# CONcrescent
CONcrescent is the fandom convention registration system.

CONcrescent has been used successfully to run BABSCon, the second-largest My Little Pony convention, every year since 2015.

## Features
CONcrescent provides everything a fandom convention needs in a registration and HR system, including:
*   Attendee registration
*   Vendor and artist applications
*   Panelist and event applications
*   Community guest applications
*   Press/media applications
*   Staff applications
*   Registration check-in and badge printing
*   Registration and application statistics with line chart
*   Fast searching over records with advanced search syntax
*   Multiple badge types
*   Customizable registration and application forms
*   Promo/discount codes for attendees
*   Blacklisting
*   Customizable email notifications
*   CSV export
*   Vendor/artist table management
*   Panel scheduling
*   Interview scheduling
*   Department hierarchy management
*   Staff org chart generation
*   Staff mailing list generation
*   Badge artwork management and layout
*   Badge preprinting
*   Payment requests
*   Multiple admin user accounts
*   Integration with PayPal for payment
*   Integration with Slack for notifications

# CONcrescent 2
CONcrescent 2 is the currently stable and properly functioning version of CONcrescent.
Usage of this version is recommended and still the default version of the CONcrescent docker-compose setup.
CONcrescent 2 also has documentation and easier setup than CONcrescent 3.

## Docker Installation
1.  Install [docker](https://docs.docker.com/engine/install/).
2.  Copy `docker-concrescent2/concrescent.example.php` to `docker-concrescent2/concrescent.php` and edit the file to fit your config.
3.  Copy `msmtprc.example` to `msmtprc` and change the settings in this file to reflect your mail server.
4.  Copy `docker-compose.example.yaml` to `docker-compose.yaml` and edit it to your liking, including potential SSL reverse proxy setups.<br>
    Specifically make sure that your `concrescent.php` timezone matches your `TZ` environment variable in the SQL container. If you use an external MySQL server, make sure the `concrescent.php` contains the right server timezone.
5.  Start concrescent with `docker compose up [-d]` and go to `http://localhost:8080/admin/doctor` to verify CONcrescent is set up correctly.
    All rows should turn green and start with **PASSED**.
6. Once all issues (if any) have been resolved, `http://localhost:8080/admin/doctor/set_perm.php` to prevent future access to the checks.
7. Log in to `http://localhost:8080/admin/` with the username and password set in the configuration file in step 2.<br>
You can now start going through each section in the side nav to set up badge types, form questions, blacklists, email notifications, rooms and tables, departments, badge artwork, admin accounts, etc.

Depending on the way you set up your `docker-compose.yaml` file you might have a different base URL than `http://localhost:8080/`.
Simply replace that in all the above URLs.

## Manual Installation
1.  Check out the `cm2` directory.
2.  Edit `cm2/config/config.php`, replacing the default values with values
    specific to your web server, PayPal account, and event. Also make sure
    to add a default admin username and password.
3.  Upload the `cm2` directory to your web server.
4.  Navigate to `cm2/admin/doctor/` to verify CONcrescent is set up correctly.
    All rows should turn green and start with **PASSED**.
5.  Once all issues (if any) have been resolved, `chmod a-x cm2/admin/doctor`.
6.  Log in to `cm2/admin/` with the username and password set in the configuration file in step 2.
7.  Go through each section in the side nav to set up badge types, form questions, blacklists,
    email notifications, rooms and tables, departments, badge artwork, admin accounts, etc.

You may rename the `cm2` directory to anything you like, move it to a subdirectory,
or even move the contents of the `cm2` directory into the root of your web server.
CONcrescent will figure out where it is installed and generate appropriate URLs
without needing to be told.

## Troubleshooting
Once set up, CONcrescent should work without issues under most web hosting configurations.
If you encounter any issues, `chmod a+x cm2/admin/doctor` and run `cm2/admin/doctor/` again
and/or check the following.

### The application is completely broken or I can't log in.
*   Make sure you're running PHP 5 or later (5.5 or later recommended). CONcrescent will not run under PHP 4.
*   Make sure the configuration file is syntactically correct and contains the correct values.
*   Make sure the database section of the configuration file is correct.
*   Make sure the default admin password is set.

### There is a blank screen or the error message "500 Internal Server Error" or "Communication Failure" when registering, submitting an application, or accepting an application.
*   Make sure the cURL extension for PHP is installed.
*   Make sure OpenSSL is up to date.
*   Make sure sendmail and PHP are correctly configured to send email.
*   Make sure the PayPal section of the configuration file is correct.

### Some images, such as QR codes, the Rooms & Tables map, or badge artwork, do not appear.
*   Make sure the GD library for PHP is installed.
*   Make sure the badge printing section of the configuration file is correct.
*   Make sure the settings on the Badge Printing Setup page are correct.

### Badge types become available or unavailable at the wrong time, or CONcrescent is treating some minors as adults or vice versa.
*   Make sure the web server's time is set correctly.
*   Make sure the MySQL server's time is set correctly.
*   Make sure the correct time zone is specified at the top of the configuration file.
*   Make sure the correct time zone is specified in the database section of the configuration file.
*   Make sure time zone tables have been loaded in the `mysql` database using [`mysql_tzinfo_to_sql`](https://dev.mysql.com/doc/refman/5.7/en/mysql-tzinfo-to-sql.html).
*   Run `cm2/admin/timecheck.php` to verify PHP time and MySQL time are synchronized.

### Search pages do not return the correct search results, or return search results with incorrect or incomplete information.
The search index may be out of date. Press Ctrl-Shift-Zero on the search page with the issue to access the "rebuild search index" page. Rebuilding the search index may take several minutes, and if the rebuilding process fails, you will have to start it all over again, so do not make a habit out of doing this.

### I'm using a QR code scanner to check people in.
Press Ctrl-Shift-8 on the check-in page to enable check-in with QR codes. When a QR code is scanned that was produced by CONcrescent, the check-in page will immediately display that person's record.

### I'm not using a QR code scanner to check people in.
Press Ctrl-Shift-9 on the check-in page to disable check-in with QR codes.
