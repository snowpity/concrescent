<?php

namespace CM3_Lib\database;

class View
{
    //$Columns = cm_SelectColumn[]
    public function __construct(public array $Columns, public ?array $Joins = null)
    {
        //TODO: Confirm that columns do not collide!
    }
}
