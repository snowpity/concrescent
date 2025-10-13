<?php

namespace App\Config;

use App\Config\Module\BadgePrinting;
use App\Config\Module\Cloudflare;
use App\Config\Module\Database;
use App\Config\Module\DefaultAdmin;
use App\Config\Module\Event;
use App\Config\Module\ExtraFeatures;
use App\Config\Module\Payment;
use App\Config\Module\Paypal;
use App\Config\Module\ReviewMode;
use App\Config\Module\System;

readonly class Configuration
{
    public function __construct(
        public System        $system,
        public Database      $database,
        public Event         $event,
        public ReviewMode    $reviewMode,
        public BadgePrinting $badgePrinting,
        public DefaultAdmin  $defaultAdmin,
        public Payment       $payment,
        public Paypal        $paypal,
        public ExtraFeatures $extraFeatures,
        public ?Cloudflare   $cloudflare = null,
    )
    {
    }
}
