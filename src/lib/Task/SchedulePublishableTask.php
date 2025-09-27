<?php

namespace App\Lib\Task;

use App\Lib\Hook\CloudflareApi;

readonly class SchedulePublishableTask
{
    public function __construct(
        private CloudflareApi $cloudflareApi,
    ) {
    }

    public function onScheduleManualUpdate(): void
    {
        try {
            $this->cloudflareApi->purgeSchedule();
        } catch (\Throwable $e) {
            \error_log('Failed to execute task '. self::class. ': '. $e->getMessage());
        }
    }
}
