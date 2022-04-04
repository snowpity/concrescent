<?php

namespace CM3_Lib\models\forms;

use CM3_Lib\database\Column as cm_Column;

class question extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Forms_Questions';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false, false, false, true),
            'context_code'		=> new cm_Column('VARCHAR', '3', false),
            'active'    => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'order'			=> new cm_Column('INT', null, false),
            'title'     => new cm_Column('VARCHAR', '255', false),
            'text'			=> new cm_Column('TEXT', null, true),
            'type'			=> new cm_Column(
                'ENUM',
                array(
                    'h1','h2','h3','p','q','he',
                    'text','textarea','url','urllist','email',
                    'radio','checkbox','select','file'
                ),
                false
            ),
            'values'		=> new cm_Column('TEXT', null, true),
            //Listed in tables. null = not available, false = available but not shown by default, true = show by default
            'listed'		=> new cm_Column('BOOLEAN', null, true, defaultValue: 'false'),
            //If this question depends on the response of another.
            //TODO: Implement
            'visible_condition'		=> new cm_Column('TEXT', null, true)
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','context_code','active','title','type');
    }

    public function verifyQuestionBelongsToEvent(int $id, int $event_id)
    {
        $bt = $this->GetByID($id, array('event_id'));
        if ($bt === false) {
            return false;
        }
        if ($bt['event_id'] != $event_id) {
            return false;
        }
        return true;
    }
}
