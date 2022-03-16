<?php

namespace CM3_Lib\util;

use CM3_Lib\database\SearchTerm;

class CurrentUserInfo
{
    private $event_id = 0;
    public function SetEventId($event_id)
    {
        $this->event_id = $event_id;
    }
    public function GetEventId()
    {
        return $this->event_id;
    }
    public function EventIdSearchTerm(string $event_id_name = 'event_id')
    {
        return new SearchTerm($event_id_name, $this->event_id);
    }
    private $contact_id = 0;
    public function SetContactId($contact_id)
    {
        $this->contact_id = $contact_id;
    }
    public function GetContactId()
    {
        return $this->contact_id;
    }
}
