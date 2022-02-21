<?php

namespace CM3_Lib\util;

use CM3_Lib\database\Table;
use Monolog\Formatter\JsonFormatter;
use Psr\Http\Message\ServerRequestInterface;

class MonologDatabaseHandler extends \Monolog\Handler\AbstractProcessingHandler
{
    public function __construct(
        private Table $targetTable,
        $level = Logger::DEBUG,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->formatter = new JsonFormatter(1, false, false);
    }
    protected function write(array $record): void
    {
        $data = array(
            'remote_addr' => $record['extra']['ip'],
            'request_uri' => $record['extra']['url'],
            'message'     => $record['message'],
            'level'       => $record['level_name'],
            'channel'     => $record['channel'],
            'data'        =>  $this->getFormatter()->format($record['context'])
        );
        //Get contact_id
        //die(print_r($record['context'], true));
        $data['contact_id'] = $record['context']['contact_id'] ?? 0;
        $data['event_id'] = $record['context']['event_id'] ?? 0;


        $this->targetTable->Create($data);
    }
}
