<?php

namespace CM3_Lib\util;

use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use MessagePack\Extension;

class EventPermissions implements Extension
{
    public PermEvent $EventPerms;
    public array $GroupPerms = array();

    public function __construct()
    {
        $this->EventPerms = new PermEvent(0);
    }

    public function getType(): int
    {
        // Arbitrary const for this project
        return 61;
    }

    public function pack(Packer $packer, mixed $value): ?string
    {
        //Just in case we get passed in a non-Permissions object
        if (!$value instanceof EventPermissions) {
            return null;
        }

        //First, pack the EventPerms
        $result = $packer->pack($value->EventPerms->getValue());

        //Then give out the GroupPerms length
        $result .= $packer->packArrayHeader(count($value->GroupPerms));

        //And then splat the group perms!
        foreach ($value->GroupPerms as $group => $perm) {
            $result .=$packer->pack($group)
            . $packer->pack($perm->getValue());
        }

        //Give back the result
        return $packer->packExt($this->getType(), $result);
    }

    public function unpackExt(BufferUnpacker $unpacker, int $extLength): EventPermissions
    {
        $result = new EventPermissions();
        //We'll always have an EventPerms if we've been packed at all
        $result->EventPerms->setValue($unpacker->unpack());
        //Check if we have any GroupPerms
        $groupPermCount = $unpacker->unpackArrayHeader();
        //Un-splat the groups!
        for (;$groupPermCount > 0;$groupPermCount--) {
            $group = $unpacker->unpack();
            $perm = new PermGroup(0);
            $perm->setValue($unpacker->unpack());
            $result->GroupPerms[$group] = $perm;
        }
        //Give back our new Permissions!
        return $result;
    }

    public function getPermEnumeration()
    {
        return array(
            'EventPerms' => $this->EventPerms->getKey(),
            'GroupPerms' => array_map(function ($perm) {
                return $perm->getKey();
            }, $this->GroupPerms)
        );
    }
}
