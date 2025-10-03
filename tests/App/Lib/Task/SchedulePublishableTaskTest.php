<?php

namespace App\Lib\Task;

use App\Lib\Hook\CloudflareApi;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

#[CoversClass(SchedulePublishableTask::class)]
class SchedulePublishableTaskTest extends TestCase
{
    use ProphecyTrait;

    public function testOnScheduleManualUpdate(): void
    {
        $cloudflareApi = $this->prophesize(CloudflareApi::class);
        $loggerSystem = $this->prophesize(LoggerInterface::class);

        $task = new SchedulePublishableTask(
            $cloudflareApi->reveal(),
            $loggerSystem->reveal(),
        );

        $cloudflareApi->purgeSchedule()->shouldBeCalledOnce();
        $loggerSystem->error(Argument::any())->shouldNotBeCalled();

        $task->onScheduleManualUpdate();
    }

    public function testOnScheduleManualUpdateWithError(): void
    {
        $cloudflareApi = $this->prophesize(CloudflareApi::class);
        $loggerSystem = $this->prophesize(LoggerInterface::class);

        $task = new SchedulePublishableTask(
            $cloudflareApi->reveal(),
            $loggerSystem->reveal(),
        );

        $cloudflareApi->purgeSchedule()->willThrow(new \Exception('Test error'));
        $loggerSystem->error('Failed to execute task App\Lib\Task\SchedulePublishableTask::onScheduleManualUpdate : Test error')->shouldBeCalledOnce();

        $task->onScheduleManualUpdate();
    }

}
