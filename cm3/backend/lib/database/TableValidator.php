<?php

namespace CM3_Lib\database;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class TableValidator
{
    private \Respect\Validation\Validator $rules;
    private array $lastValidationErrors;
    private $sourceTable;
    public function __construct(Table $sourceTable)
    {
        $this->sourceTable = $sourceTable;
        $this->rules = v::arrayType();
        $this->lastValidationErrors = array();
        //Add all the columns
        foreach ($sourceTable->ColumnDefs as $columnName => $def) {
            //Set up our validator
            $v = new \Respect\Validation\Validator();
            //Are we a custom column?
            if ($def->customPostfix != null) {
                if (
                    strpos(strtoupper($def->customPostfix), 'GENERATED') !== false
                    ||strpos(strtoupper($def->customPostfix), 'VIRTUAL') !== false
                ) {
                    //We don't have to check this
                    continue;
                }
            }

            //First, the column type
            //Is it int-like?
            if (strpos(strtoupper($def->dbType), 'INT')!==false) {
                //Is it unsigned int or bigint?
                if (strpos(strtoupper($def->dbType), 'BIG')!==false || strpos(strtoupper($def->dbType), 'UNSIGNED INT')!==false) {
                    $v = $v->anyOf(v::intVal(), v::stringType());
                }
                //PHP can handle it
                $v = $v->intVal();
            //What about floats?
            } elseif (strpos(strtoupper($def->dbType), 'FLOAT')!==false
                || strpos(strtoupper($def->dbType), 'REAL')!==false
                || strpos(strtoupper($def->dbType), 'DOUBLE')!==false
                ) {
                $v = $v->FloatVal();
            //File stream or text block
            } elseif (strpos(strtoupper($def->dbType), 'BLOB')!==false
                || strpos(strtoupper($def->dbType), 'TEXT')!==false
                ) {
                $v = $v->anyOf(v::ResourceType(), v::stringType());
            } elseif (strpos(strtoupper($def->dbType), 'CHAR')!==false
                ) {
                $v = $v->stringType();
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
            $optional = false;
            if ($def->isNullable !== false || $def->isAutoIncrement || !is_null($def->defaultValue)) {
                $v = v::Optional($v);
                $optional = true;
            }


            //Finally, add it to the rules
            $this->rules = $this->rules->key($columnName, $v, !$optional);
        }
    }

    public function addColumnValidator(string $columnName, v $v, bool $optional = false)
    {
        $this->rules = $this->rules->key($columnName, $v, !$optional);
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
        } catch (NestedValidationException $e) {
            $this->lastValidationErrors = $e->getMessages();
            return false;
        }
    }
    public function GetErrors()
    {
        return $this->lastValidationErrors;
    }
}
