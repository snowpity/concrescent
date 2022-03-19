<?php

namespace CM3_Lib\util;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\admin\user;
use CM3_Lib\models\eventinfo;
use CM3_Lib\models\application\group;
use CM3_Lib\util\Permissions;

use Branca\Branca;
use MessagePack\Packer;
use MessagePack\BufferUnpacker;

class TokenGenerator
{
    public function __construct(private user $user, private eventinfo $eventinfo, private group $group, private Branca $Branca)
    {
    }

    public function forLoginOnly($contact_id, $event_id)
    {
        $event_id = $this->checkEventID($event_id);

        //Generate the token proper
        $packer = (new Packer())
            ->extendWith(new EventPermissions());
        //Initialize payload
        $tokenPayload = $packer->pack($contact_id)
          . $packer->pack($event_id);
        return $this->Branca->encode($tokenPayload);
    }

    public function forUser($contact_id, $event_id)
    {

        //Decode and load their Permissions
        $eperms = $this->loadPermissions($contact_id);

        //TODO: Switch around event_id selection in case they're an EventAdmin or GlobalAdmin
        $event_id = $this->checkEventID($event_id);

        //Fetch the permissions for the selected event
        if (isset($eperms->EventPerms[$event_id])) {
            $perms = $eperms->EventPerms[$event_id];
        } else {
            $perms = new EventPermissions();
        }

        if ($eperms->IsGlobalAdmin ||true) {
            //Flag them as GlobalAdmin
            $perms->EventPerms->setGlobalAdmin(true);
            //Load groups for the selected event
            $eventgroups = array_column($this->group->Search(array('id'), array(
                new SearchTerm('event_id', $event_id)
            )), 'id');
            //Ensure they have all groups
            foreach ($eventgroups as $group) {
                if (!isset($perms->GroupPerms[$group])) {
                    $perms->GroupPerms[$group] = new PermGroup(0);
                }
            }
        }
        if ($perms->EventPerms->isNoPermission()) {
            $perms = null;
        }

        //Generate the token proper
        $packer = (new Packer())
            ->extendWith(new EventPermissions());
        //Initialize payload
        $tokenPayload = $packer->pack($contact_id)
          . $packer->pack($event_id)
          . ($perms != null ? $packer->pack($perms) : '');

        $result = array();
        $result['event_id'] = $event_id;
        $result['token'] = $this->Branca->encode($tokenPayload);

        if ($perms != null) {
            $result['permissions'] = $perms->getPermEnumeration();
        }

        return $result;
    }

    public function loadPermissions($contact_id): UserPermissions
    {
        //Fetch their permissions (if they have any)
        $founduser = $this->user->GetByIDorUUID($contact_id, null, array('permissions'));

        if ($founduser !== false && !is_null($founduser['permissions'])) {
            $unpacker = (new BufferUnpacker())
                ->extendWith(new UserPermissions())
                ->extendWith(new EventPermissions());

            $unpacker->reset($founduser['permissions']);
            return $unpacker->unpack();
        } else {
            return new UserPermissions();
        }
    }

    public function setPermissions($contact_id, UserPermissions $newPerms)
    {
        //Fetch their permissions (if they have any)
        $founduser = $this->user->GetByIDorUUID($contact_id, null, array('contact_id'));

        if ($founduser !== false) {
            $packer = (new Packer())
                ->extendWith(new UserPermissions())
                ->extendWith(new EventPermissions());

            $founduser['permissions'] = $packer->pack($newPerms);

            $this->user->Update($founduser);
        } else {
            throw new Exception('User does not exist');
        }
    }

    public function checkEventID($event_id)
    {
        //Determine the event ID if not provided
        $thedate = date("Y/m/d");
        $eventresult = $this->eventinfo->Search(
            array('id'),
            terms: array(
                //This probably doesn't work like we think?
                new SearchTerm('id', $event_id, EncapsulationFunction: 'ifnull(?,0)', EncapsulationColumnOnly:false),
                new SearchTerm('', null, TermType: 'OR', subSearch:array(
                new SearchTerm('date_end', $thedate, ">="),
                new SearchTerm('active', true),
                new SearchTerm('', CompareValue: $event_id, Raw: '? IS NULL')
              ))
        ),
            order: array(
            'date_start'=> false
        ),
            limit: 1
        );

        if (count($eventresult) == 0) {
            throw new \Exception("Invalid event_id or event not available");
        }

        return $eventresult[0]['id'];
    }
}
