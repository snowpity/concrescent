<?php

namespace CM3_Lib\database;

class ColumnIndex
{
    //string => bool array, true means add DESC
    public function __construct(public array $Columns, public string $IndexType = '')
    {
    }
    public function GetCreateString($indexName): string
    {
        //Preamble
        switch (strtolower($this->IndexType)) {
            case 'primary key':
                $sqlText = 'CONSTRAINT PRIMARY KEY ';
                break;
            case 'unique key':
                $sqlText = 'CONSTRAINT `' . $indexName . '` UNIQUE KEY ';
                break;
            case 'unique':
                $sqlText = 'CONSTRAINT `' . $indexName . '` UNIQUE ';
                break;
            case 'fulltext':
                $sqlText = 'FULLTEXT `' . $indexName . '` ';
                break;
            default:
                $sqlText = 'INDEX `' . $indexName . '` ';
                break;
        }
        //Column definitions
        $sqlText .= '(';
        foreach ($this->Columns as $columnName => $isDesc) {
            $sqlText .= '`' . $columnName .'` ' .
            ($isDesc ? 'DESC ' : '') . ', ';
        }
        //Snip the trailing comma and add in a closing parenthesis...
        $sqlText = substr($sqlText, 0, -2) . ') ';

        return $sqlText;
    }
}
