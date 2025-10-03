<?php

namespace App\Lib\Task;

use App\Lib\Hook\CloudflareApi;
use Psr\Log\LoggerInterface;

readonly class SchedulePublishableTask
{
    public function __construct(
        private CloudflareApi $cloudflareApi,
        private LoggerInterface $loggerSystem,
    ) {
    }

    public function onScheduleManualUpdate(): void
    {
        try {
            $this->cloudflareApi->purgeSchedule();
        } catch (\Throwable $e) {
            $this->loggerSystem->error('Failed to execute task '. __METHOD__ .' : '. $e->getMessage());
        }
    }
}
