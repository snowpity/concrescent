<?php

namespace CM3_Lib\database;

class Join
{
    //$onColumns = left -> right
    //$Direction - '', 'LEFT', 'RIGHT', etc. Default 'INNTER'
    public function __construct(
        public Table $Table,
        public array $OnColumns,
        public string $Direction = 'INNER',
        public ?string $alias = null,
        public ?array $subQSelectColumns = null,
        public ?array $subQSearchTerms = null
    ) {
        //TODO: Confirm that columns do not collide!
        //TODO: Confirm alias is matching in columns!
    }
}
