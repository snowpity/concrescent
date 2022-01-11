<?php

namespace CM3_Lib\database;

class SearchTerm
{
    //Operation: AND, OR, subAND, subOR, RAW
    //EncapsulationColumnOnly: If null, applies function to both sides. True, applies to db column. False applies to value provided
    public function __construct(
        public  string $ColumnName,
        public  mixed $CompareValue,
        public  string $Operation = '=',
        public  string $TermType = 'AND',
        public ?array $subSearch = null,
        public ?string $EncapsulationFunction = null,
        public ?bool $EncapsulationColumnOnly = null,
        public ?string $JoinedTableAlias = null,
        public ?string $Raw = null
    ) {
    }
}
