<?php

namespace CM3_Lib\database;

class Column
{
    public function __construct(
        public string $dbType,
        public string|array|null $lengthOrEnumValues = null,
        public ?bool $isNullable = true,
        public bool $isPrimary = false,
        public bool $isUnique = false,
        public bool $isKey = false,
        public ?string $defaultValue = null,
        public bool $isAutoIncrement = false,
        public ?string $customPostfix = null
    ) {
    }
    //TODO: Maybe make a helper function to parse out enum values?

    public function GetCreateString(): string
    {
        $result = $this->dbType;
        if (isset($this->lengthOrEnumValues)) {
            $result .= '(';
            if (gettype($this->lengthOrEnumValues) == 'string') {
                $result .=  $this->lengthOrEnumValues;
            } else {
                foreach ($this->lengthOrEnumValues as $value) {
                    $result .= "'$value', ";
                }
                //Snip the trailing comma and add in a closing parenthesis...
                $result = substr($result, 0, -2);
            }
            $result .= ')';
        }

        $result .= ' ' .
        (isset($this->isNullable) ? ($this->isNullable ? '' : 'NOT ') . 'NULL ' : '').
        ($this->isPrimary ? 'PRIMARY ' . ($this->isKey ? ' KEY ' : '') : '') .
        ($this->isUnique ? 'UNIQUE ' . ($this->isKey ? ' KEY ' : '') : '') .
        (isset($this->defaultValue) ? 'DEFAULT ' . $this->defaultValue . ' ' : '') .
        ($this->isAutoIncrement ? 'AUTO_INCREMENT ' : '') .
        (isset($this->customPostfix) ? ' ' .$this->customPostfix . ' ' : '');
        return $result;
    }
    public function GetBindParamCode(): string
    {
        //Is it int-like?
        if (strpos(strtoupper($this->dbType), 'INT')!==false) {
            //Is it unsigned int or bigint?
            if (strpos(strtoupper($this->dbType), 'BIG')!==false || strpos(strtoupper($this->dbType), 'UNSIGNED INT')!==false) {
                return 's';
            }
            //PHP can handle it
            return 'i';
        }
        if (strpos(strtoupper($this->dbType), 'FLOAT')!==false
        || strpos(strtoupper($this->dbType), 'REAL')!==false
        || strpos(strtoupper($this->dbType), 'DOUBLE')!==false
        ) {
            return 'd';
        }

        if (strpos(strtoupper($this->dbType), 'BLOB')!==false) {
            return 'b';
        }
        return 's';
    }
}
