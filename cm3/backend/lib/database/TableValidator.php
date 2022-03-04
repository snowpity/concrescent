<?php

namespace CM3_Lib\database;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class TableValidator
{
    private ChainedValidator $rules;
    private array $lastValidationErrors;
    private $sourceTable;
    public function __construct(Table $sourceTable)
    {
        $this->sourceTable = $sourceTable;
        $this->rules = v::arrayType();
        //Add all the columns
        foreach ($sourceTable->ColumnDefs as $columnName => $def) {
            //Set up our validator
            $v = new Respect\Validation\Validator();
            //First, the column type
            //Is it int-like?
            if (strpos(strtoupper($this->dbType), 'INT')!==false) {
                //Is it unsigned int or bigint?
                if (strpos(strtoupper($this->dbType), 'BIG')!==false || strpos(strtoupper($this->dbType), 'UNSIGNED INT')!==false) {
                    $v = $v->anyOf(v::intVal(), v::stringType());
                }
                //PHP can handle it
                $v = $v->intVal();
            //What about floats?
            } elseif (strpos(strtoupper($this->dbType), 'FLOAT')!==false
                || strpos(strtoupper($this->dbType), 'REAL')!==false
                || strpos(strtoupper($this->dbType), 'DOUBLE')!==false
                ) {
                $v = $v->FloatVal();
            //File stream or string
            } elseif (strpos(strtoupper($this->dbType), 'BLOB')!==false
                || strpos(strtoupper($this->dbType), 'TEXT')!==false
                ) {
                $v = $v->anyOf(v::ResourceType(), v::stringType());
            } else {
                //We have no idea, leave it alone I guess?
            }

            //Do we have a length?
            if (is_numeric($def->lengthOrEnumValues)) {
                //Coerce to a number
                $v = $v->Length(null, $def->lengthOrEnumValues + 0);
            } elseif (is_array($def->lengthOrEnumValues)) {
                $v = $v->In($def->lengthOrEnumValues);
            }

            //Are we required?
            if ($def->isNullable == true) {
                $v = v::Optional($v);
            }


            //Finally, add it to the rules
            $this->rules = $this->rules->key($columnName, $v);
        }
    }

    public function addColumnValidator(string $columnName, Validator $v)
    {
        $this->rules = $this->rules->key($columnName, $v);
    }

    public function Validate(&$data): bool
    {
        //Clear any prior errors
        $lastValidationErrors = array();
        //Trim all the stringy values
        array_walk($data, function (&$value, $columnName) {
            if (isset($this->sourceTable->ColumnDefs[$columnName])
            && $this->sourceTable->ColumnDefs[$columnName]->GetBindParamCode() != 'b'
            && is_string($value)) {
                $value = trim($value);
            }
        });

        //Perform the actual validation
        try {
            $this->rules->assert($data);
            return true;
        } catch (\NestedValidationException $e) {
            $this->lastValidationErrors = $e->getMessages();
            return false;
        }
    }
    public function GetErrors()
    {
        return $this->lastValidationErrors;
    }
}
