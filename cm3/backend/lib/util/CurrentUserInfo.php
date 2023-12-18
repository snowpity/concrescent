<?php

namespace CM3_Lib\util;
use MessagePack\BufferUnpacker;

use CM3_Lib\util\EventPermissions;

use CM3_Lib\database\SearchTerm;

class CurrentUserInfo
{
    public function __construct(
        private \CM3_Lib\models\contact $contact,
    ) {
        $this->perms = new EventPermissions();
    }

    public function fromToken(string $token){

            //Load up the unpacker
            $unpacker = (new BufferUnpacker())
                ->extendWith(new EventPermissions());
            $unpacker->reset($token);

            //Get the Contact ID first
            $this->contact_id = $unpacker->unpack();
            //And their selected event ID
            $this->event_id = $unpacker->unpack();

            $this->perms = new EventPermissions();
            //Does this token have permissions?
            if ($unpacker->hasRemaining()) {
                //Ooh, has admin permissions! Decode that...
                $this->perms = $unpacker->unpack();
            }
    }

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
    public function GetContactEmail(?int $contact_id = null): string
    {
        if ($contact_id == null) {
            $contact_id = $this->contact_id;
        }
        $result = $this->contact->GetByID($contact_id, array('email_address'));
        if ($result === false) {
            return '';
        }
        return $result['email_address'];
    }
    public function GetContactName(?int $contact_id = null): string
    {
        if ($contact_id == null) {
            $contact_id = $this->contact_id;
        }
        $result = $this->contact->GetByID($contact_id, array('real_name'));
        if ($result === false) {
            return '';
        }
        return $result['real_name'];
    }

    private EventPermissions $perms;
    public function SetPerms($perms)
    {
        $this->perms = $perms;
    }
    public function GetPerms()
    {
        return $this->perms;
    }

    public function HasEventPerm(int $checkPerm)
    {
        if ($this->perms->EventPerms->isGlobalAdmin() || $this->perms->EventPerms->isEventAdmin()) {
            return true;
        }
        return ($this->perms->EventPerms->getValue() & $checkPerm) == $checkPerm;
    }
    public function HasGroupPerm(int $groupId, int $checkPerm)
    {
        if ($this->perms->EventPerms->isGlobalAdmin() || $this->perms->EventPerms->isEventAdmin()) {
            return true;
        }
        if (!isset($this->perms->GroupPerms[$groupId])) {
            return false;
        }
        return ($this->perms->GroupPerms[$groupId]->getValue() & $checkPerm) == $checkPerm;
    }
}
