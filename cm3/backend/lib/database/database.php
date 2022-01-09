<?php

require_once dirname(__FILE__).'/../../config/config.php';

class cm_Db
{
    public $table_prefix;
    public $connection;
    public $known_tables; //Tables we know exist. bool -> If the schema has been validated

    public function __construct()
    {
        /* Load configuration */
        $config = $GLOBALS['cm_config']['database'];
        $this->table_prefix = $config['prefix'];

        /* Connect to database */
        $this->connection = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );

        /* Set text encoding */
        $this->connection->set_charset('utf8');

        /* Set time zone */
        $stmt = $this->connection->prepare('set time_zone = ?');
        $stmt->bind_param('s', $config['timezone']);
        $stmt->execute();
        $stmt->close();

        //TODO: Hash table definition and compare with class data? Store check time to reduce extra overhead...
        /* Load known tables */
        $this->known_tables = array();
        $stmt = $this->connection->prepare(
            'SELECT table_name '.
            'FROM information_schema.tables '.
            'WHERE table_schema = ?'
        );
        $stmt->bind_param('s', $config['database']);
        $stmt->execute();
        $stmt->bind_result($table);
        while ($stmt->fetch()) {
            $this->known_tables[$table] = false;
        }
        $stmt->close();
    }

    public function table_def($table, $def)
    {
        $tn = $this->table_prefix . $table;
        if (!isset($this->known_tables[$tn])) {
            $this->known_tables[$tn] = true;
            $this->connection->query(
                'CREATE TABLE IF NOT EXISTS '.
                '`' . $tn . '` '.
                '(' . $def . ')'
            );
        }
    }

    public function table_name($table)
    {
        return '`' . $this->table_prefix . $table . '`';
    }

    public function table_is_empty($table)
    {
        $tn = $this->table_name($table);
        $result = $this->connection->query('SELECT 1 FROM ' . $tn . ' LIMIT 1');
        if ($result) {
            $is_empty = !$result->num_rows;
            $result->close();
            return $is_empty;
        } else {
            return true;
        }
    }

    public function now()
    {
        $result = $this->connection->query('SELECT NOW()');
        $row = $result->fetch_row();
        $now = $row[0];
        $result->close();
        return $now;
    }

    public function uuid()
    {
        $result = $this->connection->query('SELECT UUID()');
        $row = $result->fetch_row();
        $uuid = $row[0];
        $result->close();
        return $uuid;
    }

    public function curdatetime()
    {
        $result = $this->connection->query('SELECT CURDATE(), CURTIME()');
        $row = $result->fetch_row();
        $date = $row[0];
        $time = $row[1];
        $result->close();
        return array($date, $time);
    }

    public function timezone()
    {
        $result = $this->connection->query('SELECT @@global.time_zone, @@session.time_zone');
        $row = $result->fetch_row();
        $global = $row[0];
        $session = $row[1];
        $result->close();
        return array($global, $session);
    }

    public function characterset()
    {
        $results = array();
        $result = $this->connection->query('SHOW VARIABLES LIKE \'character\\_set\\_%\'');
        while ($row = $result->fetch_row()) {
            $results[$row[0]] = $row[1];
        }
        $result->close();
        return $results;
    }
}

class cm_Column
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
class cm_ColumnIndex
{
    //string => bool array, true means add DESC
    public function __construct(public array $Columns, public string $IndexType = '')
    {
    }
    public function GetCreateString($indexName): string
    {
        //Preamble
        switch (strtolower($ix->IndexType)) {
            case 'primary key':
                $sqlText = 'CONSTRAINT PRIMARY KEY ';
                break;
            case 'unique key':
                $sqlText = 'CONSTRAINT `' . $indexName . '` UNIQUE KEY ';
                break;
            case 'unique':
                $sqlText = 'CONSTRAINT `' . $indexName . '` UNIQUE ';
                break;
            default:
                $sqlText = 'INDEX `' . $indexName . '` ';
                break;
        }
        //Column definitions
        $sqlText .= '(';
        foreach ($ix->$Columns as $columnName => $isDesc) {
            $sqlText .= '`' . $columnName .'` ' .
            ($isDesc ? 'DESC ' : '') . ', ';
        }
        //Snip the trailing comma and add in a closing parenthesis...
        $sqlText = substr($sqlText, 0, -2) . ') ';
    }
}
class cm_SelectColumn
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

class cm_SearchTerm
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
class cm_Join
{
    //$onColumns = left -> right
    //$Direction - '', 'LEFT', 'RIGHT', etc. Default 'INNTER'
    public function __construct(
        public cm_Table $Table,
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
class cm_View
{
    //$Columns = cm_SelectColumn[]
    public function __construct(public array $Columns, public ?array $Joins = null)
    {
        //TODO: Confirm that columns do not collide!
    }
}
abstract class cm_Table
{
    //Associated database connection
    protected $cm_db;
    protected string $TableName;
    //String-keyed array of cm_Column
    public array $ColumnDefs;
    //string-keyed array of cm_ColumnIndex
    protected array $IndexDefs;
    //These must be present in some way if we're referencing an entry
    //name -> bool if it's needed for Insert as well as update
    public array $PrimaryKeys;
    //If an empty array is provided for the return columns in Search, these will be returned by default
    public array $DefaultSearchColumns;
    //Availalbe name => cm_View
    public array $Views;

    //Sets up table definitions above
    abstract protected function setupTableDefinitions(): void;

    public function __construct(cm_Db $cm_db)
    {
        $this->cm_db = $cm_db;
        $this->setupTableDefinitions();
        $this->CheckTable();
    }

    public function CheckTable(bool $validateSchema = false): bool
    {
        if (isset($this->cm_db->known_tables[$this->TableName])) {
            if ($this->cm_db->known_tables[$this->TableName] == true || !$validateSchema) {
                return true;
            }
            //TODO: Validate schema
        }
        //Doesn't exist, go ahead and create it!
        //Using "If not exists" just in case we were mistaken
        $sqlText = 'CREATE TABLE IF NOT EXISTS ' . $this->dbTableName() . ' (';
        //Sequence the columns
        foreach ($this->ColumnDefs as $columnName => $c) {
            $sqlText .= '`' . $columnName .'` ' . $c->GetCreateString() . ', ';
        }
        //Sequence the indexes
        foreach ($this->IndexDefs as $indexName => $ix) {
            $sqlText .= $ix->GetCreateString($indexName) . ', ';
        }
        //Snip the trailing comma...
        $sqlText = substr($sqlText, 0, -2) .');';

        //Finally, execute
        $result = $this->cm_db->known_tables[$this->TableName] = !!$this->cm_db->connection->query($sqlText);
        if (!$result) {
            error_log("Error creating table $this->TableName with SQL: $sqlText");
        }
        return $result;
    }
    public function dbTableName(): string
    {
        return $this->cm_db->table_name($this->TableName);
    }

    public function Create(array $entryData)
    {
        return $this->_createOrUpdate_entry($entryData, true);
    }
    public function Update(array $entryData)
    {
        return $this->_createOrUpdate_entry($entryData, false);
    }

    protected function _createOrUpdate_entry(array $entrydata, bool $isNew)
    {
        //Do some initial checking
        $failCheck = false;
        $paramCodes = '';
        $paramData = array();
        $paramNames = array();
        $paramWhereCodes = '';
        $paramWhereData = array();
        $paramWhereNames = array();
        foreach ($this->ColumnDefs as $columnName => $columnDef) {
            if (isset($entrydata[$columnName])) {
                //It was provided. Is it good?
                if (gettype($entrydata[$columnName]) == 'array') {
                    //We don't support arrays as parameters
                    error_log("Error creating/updating entry for $this->TableName, column $columnName was given an array value but that's not supported.");
                    $failCheck = true;
                }
                if ($columnDef->isAutoIncrement && $isNew) {
                    error_log("Error creating entry for $TableName, column $columnName was given a value despite being AutoIncrement, and we're not supposed to.");
                    $failCheck = true;
                }
                if ($isNew || !isset($this->PrimaryKeys[$columnName])) {
                    $paramNames[] = $columnName;
                    $paramCodes .= $columnDef->GetBindParamCode();
                    $paramData[] = &$entrydata[$columnName];
                } else {
                    $paramWhereNames[] = $columnName;
                    $paramWhereCodes .= $columnDef->GetBindParamCode();
                    $paramWhereData[] = &$entrydata[$columnName];
                }
            } elseif ($isNew) {
                //Was NOT provided. Should it have been?
                if (!isset($this->PrimaryKeys[$columnName]) && $columnDef->isNullable === false && $columnDef->defaultValue == null) {
                    error_log("Error creating entry for $this->TableName, column $columnName was not given a value.");
                    $failCheck = true;
                }
            } else {
                //Not provided but we're updating, is it a primary key?
                if (isset($this->PrimaryKeys[$columnName])) {
                    error_log("Error updating entry for $this->TableName, column $columnName was not given a value but needs one.");
                    $failCheck = true;
                }
            }
        }

        //Did we fail?
        if ($failCheck) {
            error_log('Submitted data:\n' . print_r($entrydata, true));
            return false;
        }

        //Prepare our statement
        $sqlText = ($isNew ? 'INSERT INTO ' : 'UPDATE ') .  $this->dbTableName() . ' SET ';
        foreach ($paramNames as $columnName) {
            $sqlText .= '`' . $columnName .'` = ? , ';
        }
        //Cut out the ending comma close it out
        $sqlText = substr($sqlText, 0, -2);

        //Add our where clause if updating
        if (!$isNew) {
            $sqlText .= ' WHERE ';
            $firstTermAdded = false;
            foreach ($paramWhereNames as $columnName) {
                if ($firstTermAdded) {
                    $sqlText .= ' AND ';
                } else {
                    $firstTermAdded = true;
                }
                $sqlText .= '`' . $columnName .'` = ? ';
            }
            $sqlText .= ' LIMIT 1'; //for safety
        }

        //Tie off the statement
        $sqlText .= ';';

        //Get us a statement
        $stmt = $this->cm_db->connection->prepare($sqlText);
        //And tell it about our parameters
        //NOTE: Based off of https://www.php.net/manual/en/mysqli-stmt.bind-param.php#107154
        array_unshift($paramData, $paramCodes.$paramWhereCodes);
        ( new ReflectionMethod('mysqli_stmt', 'bind_param'))->invokeArgs($stmt, array_merge($paramData, $paramWhereData));
        //If we have blobs to send, do it now
        foreach (str_split($paramCodes) as $ix => $pcode) {
            if ($pcode !== 'b') {
                continue;
            }
            //Determine if the variable is a file reference or raw data
            if (is_resource($paramData[$ix])) {
                $fp = $paramData[$ix];
            } else {
                //Open the object as a string resource
                $fp = fopen('php://memory', 'r+');
                fwrite($fp, $paramData[$ix]);
                rewind($fp);
            }
            //Toss it into the pipe
            while (!feof($fp)) {
                $stmt->send_long_data($ix, fread($fp, 65536));
            }
            fclose($fp);
        }
        //Do it!
        if ($stmt->execute() && $this->cm_db->connection->affected_rows > 0) {
            //Seemed fine, return the ID
            $autoid = $this->cm_db->connection->insert_id;
            $id = array_intersect_key($entrydata, $this->PrimaryKeys);
            if ($autoid) {
                //Find the AUTO_INCREMENT column and give it back
                $columnName = array_keys(array_filter($this->ColumnDefs, function ($v) {
                    return $v->isAutoIncrement;
                }))[0];
                if (isset($columnName)) {
                    $id[$columnName] = $autoid;
                }
            }
        } else {
            error_log("Error while attempting to " . ($isNew ? 'create' : 'update') . " entry for $this->TableName:\n" . print_r($this->cm_db->connection->error, true));
            error_log('Submitted data:\n' . print_r($entrydata, true));
            error_log('SQL: ' . $sqlText . '\n');
            error_log('LastError: ' . $this->cm_db->connection->error);
            $id = false;
        }
        $stmt->close();
        return $id;
    }

    public function Delete(array $entrydata)
    {

        //Do some initial checking
        $failCheck = false;
        $paramWhereNames = array();
        $paramWhereCodes = '';
        $paramWhereData = array();
        foreach ($this->ColumnDefs as $columnName => $columnDef) {
            if (isset($entrydata[$columnName])) {
                if (gettype($entrydata[$columnName]) == 'array') {
                    //We don't support arrays as parameters
                    error_log("Error deleting entry for $this->TableName, column $columnName was given an array value but that's not supported.");
                    $failCheck = true;
                }
                if (isset($this->PrimaryKeys[$columnName])) {
                    $paramWhereNames[] = $columnName;
                    $paramWhereCodes .= $columnDef->GetBindParamCode();
                    $paramWhereData[] = &$entrydata[$columnName];
                }
            } else {
                //Not provided, is it a primary key?
                if (isset($this->PrimaryKeys[$columnName])) {
                    error_log("Error deleting entry for $this->TableName, column $columnName was not given a value but needs one.");
                    $failCheck = true;
                }
            }
        }

        //Did we fail?
        if ($failCheck) {
            error_log('Submitted data:\n' . print_r($entrydata, true));
            return false;
        }
        //Prepare our statement
        $sqlText = 'DELETE FROM ' .  $this->dbTableName(). ' WHERE ';

        $firstTermAdded = false;
        foreach ($paramWhereNames as $columnName) {
            if ($firstTermAdded) {
                $sqlText .= ' AND ';
            } else {
                $firstTermAdded = true;
            }
            $sqlText .= '`' . $columnName .'` = ? ';
        }
        $sqlText .= ' LIMIT 1;'; //for safety

        //Get us a statement
        $stmt = $this->cm_db->connection->prepare($sqlText);
        //And tell it about our parameters
        //NOTE: Based off of https://www.php.net/manual/en/mysqli-stmt.bind-param.php#107154
        array_unshift($paramWhereData, $paramWhereCodes);
        ( new ReflectionMethod('mysqli_stmt', 'bind_param'))->invokeArgs($stmt, $paramWhereData);
        //Do it!
        if ($stmt->execute()) {
            //Seemed fine, return the ID
            $count = $this->cm_db->connection->affected_rows;
        } else {
            error_log("Error while attempting to delete entry for $this->TableName:\n" . print_r($this->cm_db->connection->error, true));
            error_log('Submitted data:\n' . print_r($entrydata, true));
            $count = false;
        }
        $stmt->close();
        return $count;
    }

    public function Search(cm_View|array|string|null $columns = null, ?array $terms = null, ?array $order = null, int $limit = -1, int $offset = 0)
    {
        $groupNames = array();
        $viewName = null;
        $sqlText = 'SELECT ';

        //If columns is a string, check that the view exists
        if (gettype($columns) == 'string') {
            if (!isset($this->Views[$columns])) {
                $columns = $this->DefaultSearchColumns;
                error_log('A view named '. $columns . ' was specified for ' . $this->TableName . ' but isn\'t defined!');
            } else {
                $viewName = $columns;
                if (is_callable($this->Views[$viewName])) {
                    //A function that returns the view
                    $view = $this->Views[$viewName]($this);

                    $viewJoins = $view->Joins;
                    $columns = $view->Columns;
                } else {
                    //Just a normal view cm_View
                    $viewJoins = $this->Views[$viewName]->Joins;
                    $columns = $this->Views[$viewName]->Columns;
                }
            }
        }

        //If columns is a view, slide that in
        if (gettype($columns) == 'object' && is_a($columns, 'cm_View')) {
            $viewName = 'dynamic';
            $viewJoins = $columns->Joins;
            $columns = $columns->Columns;
        }

        //If columns isn't specified, add in the defaults or the primary keys if all else fails
        if ($columns != null && gettype($columns) == 'array' && count($columns) == 0) {
            $columns = $this->DefaultSearchColumns;
        }
        if ($columns == null) {
            $columns = array_keys($this->PrimaryKeys);
        }

        //If we still don't have columns, add in everything?
        if (gettype($columns) == 'array' && count($columns) == 0) {
            $columns = array_keys($this->ColumnDefs);
        }

        foreach ($columns as $value) {
            //TODO: Check column name is correct
            //A bare string is just the column name
            if (gettype($value) == 'string') {
                $sqlText .= (isset($value->JoinedTableAlias) ? '`' . $value->JoinedTableAlias .'`.' : $this->dbTableName()) .
                '.`' . $value .'`, ';
            } elseif (is_a($value, 'cm_SelectColumn')) {
                $sqlText .= str_replace(
                    '?',
                    (isset($value->JoinedTableAlias) ? '`' . $value->JoinedTableAlias .'`.' : $this->dbTableName() .'.').
                        '`' . $value->ColumnName .'`',
                    $value->EncapsulationFunction != null ? $value->EncapsulationFunction : '?'
                );
                if ($value->Alias !== null) {
                    $sqlText .= ' as `' . $value->Alias . '`';
                }
                //Are we grouping this column?
                if ($value->GroupBy) {
                    $groupNames[] = $value->ColumnName;
                }
                $sqlText .= ', ';
            } else {
                error_log("Error while attempting to add select column for $this->TableName:\n" . print_r($this->cm_db->connection->error, true));
                error_log('Submitted column data:\n' . print_r($value, true));
            }
        }

        //Snip the trailing comma
        $sqlText = substr($sqlText, 0, -2) . ' FROM ' . $this->dbTableName() . ' ';

        $whereCodes = '';
        $whereData = array();

        //Check if we're doing joins because we're a view
        if (isset($viewName) && isset($viewJoins)) {
            foreach ($viewJoins as $join) {
                $joinSubQueryExposed = array();
                if (!isset($join->subQSelectColumns) && !isset($join->subQSearchTerms)) {
                    //Normal flat join
                    $sqlText .= $join->Direction . ' JOIN ' . $join->Table->dbTableName();
                    if (isset($join->alias)) {
                        $sqlText .= ' as `' . $join->alias . '` ';
                    }
                    $joinSubQueryExposed = $join->OnColumns;
                } else {
                    //Sub-query style join. Add the select columns
                    $sqlText .= $join->Direction . ' JOIN ( SELECT ';
                    //Track columns exposed
                    $joinSubQuerygroupNames = array();

                    foreach ($join->subQSelectColumns as $value) {
                        //TODO: Check column name is correct
                        //A bare string is just the column name
                        if (gettype($value) == 'string') {
                            $sqlText .= (isset($value->JoinedTableAlias) ? '`' . $value->JoinedTableAlias .'`.' : $join->Table->dbTableName()) .
                            '`' . $value .'`, ';
                            $joinSubQueryExposed[] = $value;
                        } elseif (is_a($value, 'cm_SelectColumn')) {
                            $sqlText .= str_replace(
                                '?',
                                (isset($value->JoinedTableAlias) ? '`' . $value->JoinedTableAlias .'`.' : $join->Table->dbTableName() .'.').
                                    '`' . $value->ColumnName .'`',
                                $value->EncapsulationFunction != null ? $value->EncapsulationFunction : '?'
                            );
                            if ($value->Alias !== null) {
                                $sqlText .= ' as ' . $value->Alias;
                                $joinSubQueryExposed[] = $value->Alias;
                            } else {
                                $joinSubQueryExposed[] = $value->ColumnName;
                            }

                            //Are we grouping this column?
                            if ($value->GroupBy) {
                                $joinSubQuerygroupNames[] = $value->ColumnName;
                            }
                            $sqlText .= ', ';
                        } else {
                            error_log("Error while attempting to add select column in subQuery join for $join->Table->dbTableName:\n" . print_r($this->cm_db->connection->error, true));
                            error_log('Submitted column data:\n' . print_r($value, true));
                        }
                    }

                    //Snip the trailing comma
                    $sqlText = substr($sqlText, 0, -2) . ' FROM ' . $join->Table->dbTableName() . ' ';

                    //Do we have any terms?
                    if ($join->subQSearchTerms != null && count($join->subQSearchTerms)) {
                        $sqlText .= 'WHERE ' . $join->Table->_WhereBuilder($join->subQSearchTerms, $whereCodes, $whereData);
                    }

                    //Are we grouping?
                    if (count($joinSubQuerygroupNames)) {
                        $sqlText .= ' GROUP BY ';
                        foreach ($joinSubQuerygroupNames as $value) {
                            $sqlText .= '`' . $value .'`, ';
                        }

                        //Snip the trailing comma
                        $sqlText = substr($sqlText, 0, -2);
                    }

                    //End the subquery
                    $sqlText .= ') ';


                    if (isset($join->alias)) {
                        $sqlText .= ' as `' . $join->alias . '` ';
                    } else {
                        //No alias, call the subquery the join table name
                        $sqlText .= ' as ' . $join->Table->dbTableName() .' ';
                    }
                }
                //Map the provided columns
                $sqlText .= ' ON ';
                $firstInGroup = true;
                foreach ($join->OnColumns as $columnA => $columnB) {
                    if (in_array($columnB, $joinSubQueryExposed)) {
                        if ($firstInGroup) {
                            $firstInGroup = false;
                        } else {
                            $sqlText .= ' AND '	;
                        }
                        $sqlText .= $this->dbTableName() .'.`' . $columnA . '` = ' .
                        (isset($join->alias) ? '`' . $join->alias . '`' : $join->Table->dbTableName()) .
                        '.`'. $columnB . '` ';
                    } else {
                        error_log('Unable to handle join parameter ' . $columnB . ' because it wasn\'t included?');
                    }
                }
            }
        }


        //Do we have any terms?
        if ($terms != null && count($terms)) {
            $sqlText .= 'WHERE ' . $this->_WhereBuilder($terms, $whereCodes, $whereData);
        }

        //Are we grouping?
        if (count($groupNames)) {
            $sqlText .= ' GROUP BY ';
            foreach ($groupNames as $value) {
                $sqlText .= '`' . $value .'`, ';
            }

            //Snip the trailing comma
            $sqlText = substr($sqlText, 0, -2);
        }

        //Are we ordering?
        if ($order != null && count($order)) {
            $sqlText .= ' ORDER BY ';
            foreach ($order as $key=>$value) {
                if (gettype($value) == 'string') {
                    $sqlText .= '`' . $value .'`, ';
                } else {
                    $sqlText .= '`' . $key .'`' . ($value ? ' DESC' : '') . ', ';
                }
            }

            //Snip the trailing comma
            $sqlText = substr($sqlText, 0, -2);
        }

        //Are we limiting?
        if ($limit > -1) {
            $sqlText .= ' LIMIT ' . $limit . ' ';
        }

        //Skipping?
        if ($offset > 0) {
            $sqlText .= ' OFFSET ' . $offset . ' ';
        }

        //die($sqlText);

        //Now execute the statement...
        //Get us a statement
        $stmt = $this->cm_db->connection->prepare($sqlText);
        if ($stmt === false) {
            error_log("Error while preparing statement to Search for $this->TableName:\n" . print_r($this->cm_db->connection->error, true));
            error_log('SQL: ' . $sqlText . '\n');
            return false;
        }
        //And tell it about our parameters
        //NOTE: Based off of https://www.php.net/manual/en/mysqli-stmt.bind-param.php#107154
        array_unshift($whereData, $whereCodes);
        if (strlen($whereCodes) > 0) {
            ( new ReflectionMethod('mysqli_stmt', 'bind_param'))->invokeArgs($stmt, $whereData);
        }

        //Do it!
        if ($stmt->execute()) {
            //Seemed fine, let's get the data
            //Courtest https://www.php.net/manual/en/mysqli-stmt.bind-param.php#107154
            $resultMetaData = $stmt->result_metadata();
            if ($resultMetaData) {
                $stmtRow = array(); //this will be a result row returned from mysqli_stmt_fetch($stmt)
                $rowReferences = array();  //this will reference $stmtRow and be passed to mysqli_bind_results
                while ($field = $resultMetaData->fetch_field()) {
                    $rowReferences[] = &$stmtRow[$field->name];
                }
                $resultMetaData->free_result();
                (new ReflectionMethod('mysqli_stmt', 'bind_result'))->invokeArgs($stmt, $rowReferences); //calls mysqli_stmt_bind_result($stmt,[$rowReferences]) using object-oriented style
                $result = array();
                while ($stmt->fetch()) {
                    foreach ($stmtRow as $key => $value) {  //variables must be assigned by value, so $result[] = $stmtRow does not work (not really sure why, something with referencing in $stmtRow)
                        $row[$key] = $value;
                    }
                    $result[] = $row;
                }
                $stmt->free_result();
            } else {
                $result = $stmt->affected_rows();
            }
        } else {
            error_log("Error while attempting to Search for $this->TableName:\n" . print_r($this->cm_db->connection->error, true));
            error_log('SQL: ' . $sqlText . '\n');
            error_log('LastError: ' . $this->cm_db->connection->error);
            $result = false;
        }
        $stmt->close();
        return $result;
    }

    protected function _WhereBuilder(array $terms, string &$whereCodes, array &$whereData)
    {
        $result = '(';
        $firstInGroup = true;

        foreach ($terms as $term) {
            if ($term == null) {
                continue;
            }
            if ($firstInGroup) {
                $firstInGroup = false;
            } else {
                $result .= ' ' . $term->TermType . ' ';
            }

            //Are we a sub-clause?
            if ($term->subSearch == null) {
                //Just normal

                //determine value type code
                $typeCode = 's'; //String by default
                switch (gettype($term->CompareValue)) {
                    case 'integer': $typeCode = 'i'; break;
                    case 'double': $typeCode = 'd'; break;
                }

                //Do we have a Raw clause?
                if ($term->Raw != null) {
                    //Append it to the result
                    $result .= $term->Raw;
                    // Was there a ?
                    if (strpos($term->Raw, '?') !== false) {
                        //Add it to the parameters
                        $whereCodes .= $typeCode;
                        $whereData[] = &$term->CompareValue;
                    }
                } else {
                    //Normal term, add it in
                    $result .= str_replace(
                        '?',
                        (isset($term->JoinedTableAlias) ? '`' . $term->JoinedTableAlias . '`' : $this->dbTableName()) . '.' .
                            '`' . $term->ColumnName .'` ',
                        $term->EncapsulationFunction != null && $term->EncapsulationColumnOnly !== false ? $term->EncapsulationFunction : '?'
                    ) . $term->Operation . ' ';
                    //Is our operation an IN ?
                    if (strpos(strtolower($term->Operation), 'in') !== false) {
                        $result .= '(';
                        $firstNeedle = true;
                        foreach ($term->CompareValue as $key => $needle) {
                            if ($firstNeedle) {
                                $firstNeedle = false;
                            } else {
                                $result .= ', ';
                            }
                            $typeCode = 's'; //String by default
                            switch (gettype($needle)) {
                                case 'integer': $typeCode = 'i'; break;
                                case 'double': $typeCode = 'd'; break;
                            }
                            $result .= "?";
                            $whereCodes .= $typeCode;
                            $whereData[] = &$term->CompareValue[$key];
                        }
                        $result .= ')';
                    }
                    //Is our operation an is (not)
                    elseif (strpos(strtolower($term->Operation), 'is') !== false) {
                        //We totally ignore whatever the value is and assume it's null anyways
                        $result .= ' NULL ';
                    } else {
                        //Just a normal value
                        $result .=($term->EncapsulationFunction != null && $term->EncapsulationColumnOnly !== true) ? $term->EncapsulationFunction : '?';
                        $whereCodes .= $typeCode;
                        $whereData[] = &$term->CompareValue;
                    }
                }
            } else {
                //Sub-search. Ignore everything and recurse.
                $result .= $this->_WhereBuilder($term->subSearch, $whereCodes, $whereData);
            }
        }
        //And we're done!
        return $result .')';
    }

    public function GetByIDorUUID($id, $uuid, array $columns = null)
    {
        //Were we even provided an ID?
        if (!$id && !$uuid) {
            return false;
        }
        $terms = array();
        if (!!$id) {
            if (count($this->PrimaryKeys) == 1) {
                $terms[] = new cm_SearchTerm($this->PrimaryKeys[0], $id);
            } else {
                //TODO: multi-key not yet supported.
            }
        }
        if (!!$uuid) {
            if (isset($this->ColumnDefs['uuid_raw'])) {
                $terms[] = new cm_SearchTerm('uuid_raw', $this->PrimaryKeys[0], EncapsulationFunction: 'UUID_TO_BIN(?)', EncapsulationColumnOnly: false);
            } elseif (isset($this->ColumnDefs['uuid'])) {
                $terms[] = new cm_SearchTerm('uuid', $this->PrimaryKeys[0]);
            } else {
                //Some other UUID?
                //TODO: Maybe search for the UUID column if it's by another name?
            }
        }

        $result = $this->Search($columns, $terms, limit: 1);
        if ($result === false) {
            return false;
        } else {
            return $result[0];
        }
    }
}
