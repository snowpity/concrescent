<?php

namespace CM3_Lib\database;

abstract class Table
{
    //Associated database connection
    protected DbConnection $cm_db;
    protected string $TableName;
    //String-keyed array of Column
    public array $ColumnDefs;
    //string-keyed array of ColumnIndex
    protected array $IndexDefs;
    //These must be present in some way if we're referencing an entry
    //name -> bool if it's needed for Insert as well as update
    public array $PrimaryKeys;
    //If an empty array is provided for the return columns in Search, these will be returned by default
    public array $DefaultSearchColumns;
    //Availalbe name => View
    public array $Views;

    //Sets up table definitions above
    abstract protected function setupTableDefinitions(): void;

    public function __construct(DbConnection $cm_db)
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
            $this->checkAndThrowError("Error creating table $this->TableName", array('LastError: ' . $this->cm_db->connection->error), $sqlText, true);
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
        $errors = array();
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
                    $errors[] ="Column $columnName was given an array value but that's not supported.";
                    $failCheck = true;
                }
                if ($columnDef->isAutoIncrement && $isNew) {
                    $errors[] ="Column $columnName was given a value despite being AutoIncrement, and we're not supposed to.";
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
                if (!isset($this->PrimaryKeys[$columnName]) && $columnDef->isNullable === false && is_null($columnDef->defaultValue)) {
                    $errors[] ="Column $columnName was not given a value.";
                    $failCheck = true;
                }
            } else {
                //Not provided but we're updating, is it a primary key?
                if (isset($this->PrimaryKeys[$columnName])) {
                    $errors[] ="Column $columnName was not given a value but needs one.";
                    $failCheck = true;
                }
            }
        }

        //Did we fail?
        if ($failCheck) {
            $errors[] ='Submitted data:\n' . print_r($entrydata, true);
            $this->checkAndThrowError("Failed pre-check in creating/updating entry for $this->TableName", $errors, "");
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
        ( new \ReflectionMethod('mysqli_stmt', 'bind_param'))->invokeArgs($stmt, array_merge($paramData, $paramWhereData));
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
            $this->checkAndThrowError(
                "Error while attempting to " . ($isNew ? 'create' : 'update') . " entry for $this->TableName.",
                array(
            'Submitted data:\n' . print_r($entrydata, true),
            'LastError: ' . print_r($this->cm_db->connection->error, true)
          ),
                $sqlText
            );
            $id = false;
        }
        $stmt->close();
        return $id;
    }

    public function Delete(array $entrydata)
    {

        //Do some initial checking
        $failCheck = false;
        $errors = array();
        $paramWhereNames = array();
        $paramWhereCodes = '';
        $paramWhereData = array();
        foreach ($this->ColumnDefs as $columnName => $columnDef) {
            if (isset($entrydata[$columnName])) {
                if (gettype($entrydata[$columnName]) == 'array') {
                    //We don't support arrays as parameters
                    $errors[] ="Error deleting entry for $this->TableName, column $columnName was given an array value but that's not supported.";
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
                    $errors[] ="Error deleting entry for $this->TableName, column $columnName was not given a value but needs one.";
                    $failCheck = true;
                }
            }
        }

        //Did we fail?
        if ($failCheck) {
            $errors[] ='Submitted data:\n' . print_r($entrydata, true);
            $this->checkAndThrowError("Precheck Error deleting entry for $this->TableName", $errors, "");
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
        ( new \ReflectionMethod('mysqli_stmt', 'bind_param'))->invokeArgs($stmt, $paramWhereData);
        //Do it!
        if ($stmt->execute()) {
            //Seemed fine, return the ID
            $count = $this->cm_db->connection->affected_rows;
        } else {
            $errors[] ='Submitted data:\n' . print_r($entrydata, true);
            $errors[] ='LastError: ' . print_r($this->cm_db->connection->error, true);
            $this->checkAndThrowError("Error while attempting to delete entry for $this->TableName", $errors, $sqlText);
            $count = false;
        }
        $stmt->close();
        return $count;
    }

    public function Search(View|array|string|null $columns = null, ?array $terms = null, ?array $order = null, int $limit = -1, int $offset = 0)
    {
        $errors = array();
        $groupNames = array();
        $viewName = null;
        $sqlText = 'SELECT ';

        //If columns is a string, check that the view exists
        if (gettype($columns) == 'string') {
            if ($columns == '*') {
                //Special "everything" view
                $columns = array_keys($this->ColumnDefs);
            } elseif (!isset($this->Views[$columns])) {
                $errors[] ='A view named '. $columns . ' was specified for ' . $this->TableName . ' but isn\'t defined!';
                $columns = $this->DefaultSearchColumns;
            } else {
                $viewName = $columns;
                if (is_callable($this->Views[$viewName])) {
                    //A function that returns the view
                    $view = $this->Views[$viewName]($this);

                    $viewJoins = $view->Joins;
                    $columns = $view->Columns;
                } else {
                    //Just a normal view View
                    $viewJoins = $this->Views[$viewName]->Joins;
                    $columns = $this->Views[$viewName]->Columns;
                }
            }
        }

        //If columns is a view, slide that in
        if (gettype($columns) == 'object' && $columns instanceof View) {
            $viewName = 'dynamic';
            $viewJoins = $columns->Joins;
            $columns = $columns->Columns;
        }

        //If columns isn't specified, add in the defaults or the primary keys if all else fails
        if (!is_null($columns) && is_array($columns) && count($columns) == 0) {
            $columns = $this->DefaultSearchColumns;
        }
        if (is_null($columns)) {
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
            } elseif ($value instanceof SelectColumn) {
                $sqlText .= str_replace(
                    '?',
                    (isset($value->JoinedTableAlias) ? '`' . $value->JoinedTableAlias .'`.' : $this->dbTableName() .'.').
                        '`' . $value->ColumnName .'`',
                    $value->EncapsulationFunction != null ? $value->EncapsulationFunction : '?'
                );
                if (!is_null($value->Alias)) {
                    $sqlText .= ' as `' . $value->Alias . '`';
                }
                //Are we grouping this column?
                if ($value->GroupBy) {
                    $groupNames[] = $value->ColumnName;
                }
                $sqlText .= ', ';
            } else {
                $errors[] ="Unable to add select column:\n" . print_r($value, true);
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
                            $sqlText .= (isset($value->JoinedTableAlias) ? '`' . $value->JoinedTableAlias .'`' : $join->Table->dbTableName()) .
                            '.`' . $value .'`, ';
                            $joinSubQueryExposed[] = $value;
                        } elseif ($value instanceof SelectColumn) {
                            $sqlText .= str_replace(
                                '?',
                                (isset($value->JoinedTableAlias) ? '`' . $value->JoinedTableAlias .'`.' : $join->Table->dbTableName() .'.').
                                    '`' . $value->ColumnName .'`',
                                $value->EncapsulationFunction != null ? $value->EncapsulationFunction : '?'
                            );
                            if (!is_null($value->Alias)) {
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
                            $errors[] ="Unable to add select column in subQuery join for $join->Table->dbTableName:\n" . print_r($value, true);
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
                        $errors[] ='Unable to handle join parameter ' . $columnB . ' because it wasn\'t included?';
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
        $this->checkAndThrowError("Error while attempting to generate select query for $this->TableName.", $errors, $sqlText);

        //Now execute the statement...
        //Get us a statement
        $stmt = $this->cm_db->connection->prepare($sqlText);
        if ($stmt === false) {
            $this->checkAndThrowError(
                "Error while preparing statement to Search for $this->TableName.",
                array(
              'LastError: ' . print_r($this->cm_db->connection->error, true)
            ),
                $sqlText
            );
            return false;
        }
        //And tell it about our parameters
        //NOTE: Based off of https://www.php.net/manual/en/mysqli-stmt.bind-param.php#107154
        array_unshift($whereData, $whereCodes);
        if (strlen($whereCodes) > 0) {
            ( new \ReflectionMethod('mysqli_stmt', 'bind_param'))->invokeArgs($stmt, $whereData);
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
                (new \ReflectionMethod('mysqli_stmt', 'bind_result'))->invokeArgs($stmt, $rowReferences); //calls mysqli_stmt_bind_result($stmt,[$rowReferences]) using object-oriented style
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
            $this->checkAndThrowError(
                "Error while executing statement to Search for $this->TableName.",
                array(
              'LastError: ' . print_r($this->cm_db->connection->error, true)
            ),
                $sqlText
            );
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
            if (is_null($term)) {
                continue;
            }
            if ($firstInGroup) {
                $firstInGroup = false;
            } else {
                $result .= ' ' . $term->TermType . ' ';
            }

            //Are we a sub-clause?
            if (is_null($term->subSearch)) {
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
                $terms[] = new SearchTerm($this->PrimaryKeys[0], $id);
            } else {
                //TODO: multi-key not yet supported.
            }
        }
        if (!!$uuid) {
            if (isset($this->ColumnDefs['uuid_raw'])) {
                $terms[] = new SearchTerm('uuid_raw', $this->PrimaryKeys[0], EncapsulationFunction: 'UUID_TO_BIN(?)', EncapsulationColumnOnly: false);
            } elseif (isset($this->ColumnDefs['uuid'])) {
                $terms[] = new SearchTerm('uuid', $this->PrimaryKeys[0]);
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

    private function checkAndThrowError(string $Description, ?array $currentErrors, string $sql = "", bool $forceThrow = false)
    {
        if ($forceThrow || (!empty($currentErrors))) {
            throw new DbException($Description, $currentErrors, $sql);
        }
    }
}
