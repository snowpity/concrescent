<?php

namespace App\Container;

use App\Config\Configuration;
use App\Kernel;
use App\Lib\Database\cm_admin_db;
use App\Lib\Database\cm_attendee_db;
use App\Lib\Database\cm_badge_artwork_db;
use App\Lib\Database\cm_badge_holder_db;
use App\Lib\Database\cm_db;
use App\Lib\Database\cm_mail_db;
use App\Lib\Database\cm_misc_db;
use App\Lib\Database\cm_payment_db;
use App\Lib\Database\cm_staff_db;
use App\Lib\Hook\CloudflareApi;
use App\Lib\Log\LogLibrary;
use App\Lib\Task\SchedulePublishableTask;
use App\Lib\Task\SponsorPublishableTask;
use App\Lib\Util\cm_slack;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class Container
{
    public function __construct(
        private readonly Configuration $config,
        private readonly Kernel $kernel,
    ) {
    }

    private(set) LogLibrary $log {
        get => $this->log = new LogLibrary($this->kernel->logDir);
    }

    private(set) Environment $twig {
        get {
            if (isset($this->twig)) {
                return $this->twig;
            }

            $this->twig = new Environment(
                new FilesystemLoader($this->kernel->projectDir.'/templates'),
                [
                    'debug' => $this->kernel->isAppDebug,
                    'strict_variables' => $this->kernel->isAppDebug,
                    'cache' => $this->kernel->cacheDir.'/twig',
                ],
            );
            $this->twig->addFunction(new TwigFunction('theme_file_path', theme_file_path(...)));
            $this->twig->addFunction(new TwigFunction('resource_file_url', resource_file_url(...)));
            $this->twig->addFunction(new TwigFunction('resource_file_path', resource_file_path(...)));
            $this->twig->addFunction(new TwigFunction('get_site_url', get_site_url(...)));
            $this->twig->addFunction(new TwigFunction('get_site_path', get_site_path(...)));
            $this->twig->addFilter(new TwigFilter('price_string', price_string(...)));
            $this->twig->addFilter(new TwigFilter('cm_status_label', cm_status_label(...)));

            return $this->twig;
        }
    }

    private(set) cm_db $cm_db {
        get => $this->cm_db = new cm_db($this->config->database);
    }

    private(set) cm_admin_db $cm_admin_db {
        get => $this->cm_admin_db = new cm_admin_db($this->cm_db);
    }

    private(set) cm_attendee_db $cm_attendee_db {
        get => $this->cm_attendee_db = new cm_attendee_db($this->cm_db);
    }

    private(set) cm_staff_db $cm_staff_db {
        get => $this->cm_staff_db = new cm_staff_db($this->cm_db);
    }

    private(set) cm_badge_holder_db $cm_badge_holder_db {
        get => $this->cm_badge_holder_db = new cm_badge_holder_db(
            $this->cm_db,
            $this->cm_attendee_db,
            $this->cm_staff_db,
        );
    }

    private(set) cm_badge_artwork_db $cm_badge_artwork_db {
        get => $this->cm_badge_artwork_db = new cm_badge_artwork_db($this->cm_db);
    }

    private(set) cm_payment_db $cm_payment_db {
        get => $this->cm_payment_db = new cm_payment_db($this->cm_db);
    }

    private(set) cm_mail_db $cm_mail_db {
        get => $this->cm_mail_db = new cm_mail_db($this->cm_db);
    }

    private(set) cm_misc_db $cm_misc_db {
        get => $this->cm_misc_db = new cm_misc_db($this->cm_db);
    }

    private(set) cm_slack $cm_slack {
        get => $this->cm_slack = new cm_slack();
    }

    private(set) ?CloudflareApi $cloudflareApi {
        get {
            if (isset($this->cloudflareApi)) {
                return $this->cloudflareApi;
            }

            if (!$this->config->cloudflare) {
                return $this->cloudflareApi = null;
            }

            return $this->cloudflareApi = new CloudflareApi(
                $this->config->cloudflare,
                $this->log->cloudflare,
            );
        }
    }

    private(set) ?SchedulePublishableTask $taskSchedulePublishable {
        get {
            if (isset($this->taskSchedulePublishable)) {
                return $this->taskSchedulePublishable;
            }

            if (!$this->cloudflareApi) {
                return $this->taskSchedulePublishable = null;
            }

            return $this->taskSchedulePublishable = new SchedulePublishableTask(
                $this->cloudflareApi,
                $this->log->system,
            );
        }
    }

    private(set) ?SponsorPublishableTask $taskSponsorPublishable {
        get {
            if (isset($this->taskSponsorPublishable)) {
                return $this->taskSponsorPublishable;
            }

            if (!$this->cloudflareApi || !$this->config->extraFeatures->sponsors) {
                return $this->taskSponsorPublishable = null;
            }

            return $this->taskSponsorPublishable = new SponsorPublishableTask(
                $this->config->extraFeatures->sponsors,
                $this->cm_misc_db,
                $this->cloudflareApi,
                $this->log->system,
            );
        }
    }

}
