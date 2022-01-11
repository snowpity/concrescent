<?php

namespace CM3_Lib\database;

class SelectColumn
{
    public function __construct(
        public string $ColumnName,
        public bool $GroupBy = false,
        public ?string $EncapsulationFunction = null,
        public ?string $Alias = null,
        public ?string $JoinedTableAlias = null
    ) {
    }
}
