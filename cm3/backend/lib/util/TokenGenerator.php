<?php

namespace CM3_Lib\util;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\admin\user;
use CM3_Lib\models\eventinfo;
use CM3_Lib\models\application\group;
use CM3_Lib\util\Permissions;
use CM3_Lib\util\EventPermissions;

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
        $username = '';
        $preferences = '';
        //Decode and load their Permissions
        $eperms = $this->loadPermissionsAndPreferences($contact_id, $username, $preferences);

        //TODO: Switch around event_id selection in case they're an EventAdmin or GlobalAdmin
        $event_id = $this->checkEventID($event_id);

        //Fetch the permissions for the selected event
        if (isset($eperms->EventPerms[$event_id])) {
            $perms = $eperms->EventPerms[$event_id];
        } else {
            $perms = new EventPermissions();
        }

        if ($eperms->IsGlobalAdmin) {
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
            $result['username'] = $username;
            $result['preferences'] = $preferences;
            $result['permissions'] = $perms->getPermEnumeration();
        }

        return $result;
    }

    public function loadPermissions($contact_id): UserPermissions
    {
        //Fetch their permissions (if they have any)
        $founduser = $this->user->GetByIDorUUID($contact_id, null, array('permissions'));

        if ($founduser !== false) {
            return $this->decodePermissionsString($founduser['permissions']);
        } else {
            return new UserPermissions();
        }
    }
    public function loadPermissionsAndPreferences($contact_id, string &$username, string &$preferences): UserPermissions
    {
        //Fetch their permissions (if they have any)
        $founduser = $this->user->GetByIDorUUID($contact_id, null, array('permissions','username','preferences'));

        if ($founduser !== false) {
            $username = $founduser['username'];
            $preferences = $founduser['preferences'];
            return $this->decodePermissionsString($founduser['permissions']);
        } else {
            return new UserPermissions();
        }
    }
    public function decodePermissionsString(?string $perms): UserPermissions
    {
        if (empty($perms)) {
            return new UserPermissions();
        }

        $unpacker = (new BufferUnpacker())
                ->extendWith(new UserPermissions())
                ->extendWith(new EventPermissions());

        $unpacker->reset($perms);
        return $unpacker->unpack();
    }
    public function packPermissions(UserPermissions $Perms)
    {
        $packer = (new Packer())
                ->extendWith(new UserPermissions())
                ->extendWith(new EventPermissions());
        return $packer->pack($Perms);
    }

    public function mergePermsFromArray(UserPermissions $initialPerms, int $event_id, array $EventPerms)
    {
        //Create EventPermissions
        $newEventPerms = new EventPermissions();
        //Loop all the permissions and set them
        foreach ($EventPerms['EventPerms'] as $perm) {
            $newEventPerms->EventPerms->{'set' . $perm}(true);
        }
        //Loop all the groups and create them
        if (isset($EventPerms['GroupPerms'])) {
            foreach ($EventPerms['GroupPerms'] as $groupId => $gpermdata) {
                $gPerm = new PermGroup(0);
                //Loop all the permissions in the group and set them
                foreach ($gpermdata as $perm) {
                    $gPerm->{'set' . $perm}(true);
                }
                //Set the group
                $newEventPerms->GroupPerms[$groupId] = $gPerm;
            }
        }
        //Replace the event perms with this one!
        $initialPerms->EventPerms[$event_id] = $newEventPerms;
        return $initialPerms;
    }

    public function setPermissions($contact_id, UserPermissions $newPerms)
    {
        //Fetch their permissions (if they have any)
        $founduser = $this->user->GetByIDorUUID($contact_id, null, array('contact_id'));

        if ($founduser !== false) {
            $founduser['permissions'] = $this->packPermissions($newPerms);
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
