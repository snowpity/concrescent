<?php

namespace App\Lib\Task;

use App\Lib\Database\cm_misc_db;
use App\Lib\Hook\CloudflareApi;
use App\Lib\Log\LogLibrary;
use Psr\Log\LoggerInterface;

readonly class SponsorPublishableTask
{
    public function __construct(
        private cm_misc_db $miscDb,
        private CloudflareApi $cloudflareApi,
        private LoggerInterface $loggerSystem,
    ) {
    }

    public function onAttendeeManualUpdate(): void
    {
        global $cm_config;

        try {
            $nameCredit = $cm_config['extra_features']['sponsors']['nameCredit'] ?? null;
            $publishableCredit = $cm_config['extra_features']['sponsors']['publishableCredit'] ?? null;

            if ($nameCredit === null || $publishableCredit === null) {
                return;
            }

            $sponsorsHash = md5(
                json_encode(
                    $this->miscDb->getBadgeTypesFromQuestionAnswer(
                        $nameCredit,
                        $publishableCredit,
                    ),
                    JSON_THROW_ON_ERROR
                )
            );

            $lastHash = \apcu_fetch(self::class . '::last_known_hash');
            $lastHash = $lastHash === false || !is_string($lastHash) ? false : $lastHash;

            if ($sponsorsHash === $lastHash) {
                return;
            }

            \apcu_store(self::class . '::last_known_hash', $sponsorsHash);

            $this->cloudflareApi->purgeSponsors();
        } catch (\Throwable $e) {
            $this->loggerSystem->error('Failed to execute task '. __METHOD__ .' : '. $e->getMessage());
        }
    }
}
