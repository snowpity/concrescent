<?php

namespace CM3_Lib\Factory;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use CM3_Lib\database\Table;
use CM3_Lib\util\MonologDatabaseHandler;

/**
 * Factory.
 */
final class LoggerFactory
{
    private string $path;

    private int $level;

    private array $processors = [];
    private array $handler = [];

    private ?LoggerInterface $testLogger;

    /**
     * The constructor.
     *
     * @param array $settings The settings
     */
    public function __construct(array $settings = [])
    {
        $this->path = (string)($settings['path'] ?? '');
        $this->level = (int)($settings['level'] ?? Logger::DEBUG);

        // This can be used for testing to make the Factory testable
        if (isset($settings['test'])) {
            $this->testLogger = $settings['test'];
        }
    }

    /**
     * Build the logger.
     *
     * @param string|null $name The logging channel
     *
     * @return LoggerInterface The logger
     */
    public function createLogger(string $name = null): LoggerInterface
    {
        if (isset($this->testLogger)) {
            return $this->testLogger;
        }

        $logger = new Logger($name ?: \CM3_Lib\util\UUID::v4());

        foreach ($this->handler as $handler) {
            $logger->pushHandler($handler);
        }

        //Add in web processor
        $logger->pushProcessor(new \Monolog\Processor\WebProcessor($_SERVER));

        foreach ($this->processors as $processor) {
            $logger->pushProcessor($processor);
        }

        $this->handler = [];
        $this->processors = [];

        return $logger;
    }

    public function addProcessor(\Monolog\Processor\ProcessorInterface $processor)
    {
        $this->processors[] = $processor;
        return $this;
    }

    /**
     * Add a handler.
     *
     * @param HandlerInterface $handler The handler
     *
     * @return self The logger factory
     */
    public function addHandler(HandlerInterface $handler): self
    {
        $this->handler[] = $handler;

        return $this;
    }

    /**
     * Add rotating file logger handler.
     *
     * @param string $filename The filename
     * @param int|null $level The level (optional)
     *
     * @return self The logger factory
     */
    public function addFileHandler(string $filename, int $level = null): self
    {
        if (strlen($this->path) < 1) {
            return $this;
        }
        $filename = sprintf('%s/%s', $this->path, $filename);

        /** @phpstan-ignore-next-line */
        $rotatingFileHandler = new RotatingFileHandler($filename, 0, $level ?? $this->level, true, 0777);

        // The last "true" here tells monolog to remove empty []'s
        $rotatingFileHandler->setFormatter(new LineFormatter(null, null, false, true));

        $this->addHandler($rotatingFileHandler);

        return $this;
    }

    /**
     * Add a console logger.
     *
     * @param int|null $level The level (optional)
     *
     * @return self The logger factory
     */
    public function addConsoleHandler(int $level = null): self
    {
        /** @phpstan-ignore-next-line */
        $streamHandler = new StreamHandler('php://output', $level ?? $this->level);
        $streamHandler->setFormatter(new LineFormatter(null, null, false, true));

        $this->addHandler($streamHandler);

        return $this;
    }


    /**
     * Add rotating file logger handler.
     *
     * @param Table $table The database table to add to
     * @param int|null $level The level (optional)
     *
     * @return self The logger factory
     */
    public function addDBHandler(Table $table, int $level = null): self
    {
        $handler = new MonologDatabaseHandler($table, $level ?? $this->level);

        $this->addHandler($handler);

        return $this;
    }
}
