<?php

namespace App\Config;

use App\Config\Module\BadgePrinting;
use App\Config\Module\Cloudflare;
use App\Config\Module\CloudflarePurge;
use App\Config\Module\Database;
use App\Config\Module\DefaultAdmin;
use App\Config\Module\Event;
use App\Config\Module\ExtraFeatures;
use App\Config\Module\ExtraFeaturesSponsors;
use App\Config\Module\Payment;
use App\Config\Module\Paypal;
use App\Config\Module\ReviewMode;
use App\Config\Module\System;

readonly class ConfigurationMapper
{
    public function __construct(
    ) {
    }

    public function mapToConfiguration(
        #[\SensitiveParameter]
        array $config
    ): Configuration
    {
        try {
            return new Configuration(
                system: new System(
                    secret: $config['secret'],
                    timezone: $config['timezone'] ?? null,
                    siteOverride: $config['site-override'] ?? null,
                    themeLocation: $config['theme']['location'],
                    logDir: $config['logging']['log_dir'],
                ),
                database: new Database(
                    host: $config['database']['host'],
                    username: $config['database']['username'],
                    password: $config['database']['password'],
                    database: $config['database']['database'],
                    timezone: $config['database']['timezone'],
                ),
                event: new Event(
                    name: $config['event']['name'],
                    staffStartDate: $config['event']['staff_start_date'],
                    startDate: $config['event']['start_date'],
                    endDate: $config['event']['end_date'],
                    staffEndDate: $config['event']['staff_end_date'],
                ),
                reviewMode: new ReviewMode(
                    showAddress: $config['review_mode']['show_address'] ?? true,
                    showICE: $config['review_mode']['show_ice'] ?? true,
                ),
                badgePrinting: new BadgePrinting(
                    width: $config['badge_printing']['width'],
                    height: $config['badge_printing']['height'],
                    vertical: $config['badge_printing']['vertical'],
                    stylesheet: $config['badge_printing']['stylesheet'],
                    postUrl: $config['badge_printing']['post_url'],
                ),
                defaultAdmin: new DefaultAdmin(
                    name: $config['default_admin']['name'],
                    username: $config['default_admin']['username'],
                    password: $config['default_admin']['password'],
                ),
                payment: new Payment(
                    salesTax: $config['payment']['sales_tax'],
                ),
                paypal: new Paypal(
                    apiUrl: $config['paypal']['api_url'],
                    clientId: $config['paypal']['client_id'],
                    secret: $config['paypal']['secret'],
                    currency: $config['paypal']['currency'],
                ),
                extraFeatures: new ExtraFeatures(
                    sponsors: $config['extra_features'] ? new ExtraFeaturesSponsors(
                        nameCredit: $config['extra_features']['sponsors']['nameCredit'],
                        publishableCredit: $config['extra_features']['sponsors']['publishableCredit'],
                    ) : null,
                ),
                cloudflare: $config['cloudflare'] ? new Cloudflare(
                    bearerToken: $config['cloudflare']['bearer_token'],
                    purge: $config['cloudflare']['purge'] ? new CloudflarePurge(
                        zoneId: $config['cloudflare']['purge']['zone_id'],
                        sponsorFiles: $config['cloudflare']['purge']['schedule_files'] ?? $config['cloudflare']['purge']['files'] ?? [],
                        scheduleFiles: $config['cloudflare']['purge']['sponsor_files'] ?? [],
                    ) : null,
                ) : null,
            );
        } catch (\Throwable $exception) {
            die('Error reading the configuration data. Please verify no field is missing. Consult example config files and documentation. Error message was : '. $exception->getMessage());
        }
    }
}
