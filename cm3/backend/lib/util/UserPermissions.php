<?php

namespace CM3_Lib\util;

use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use MessagePack\Extension;

class UserPermissions implements Extension
{
    public bool $IsGlobalAdmin = false;
    public array $EventPerms = array();

    public function __construct()
    {
    }

    public function getType(): int
    {
        // Arbitrary const for this project
        return 58;
    }

    public function pack(Packer $packer, mixed $value): ?string
    {
        //Just in case we get passed in a non-Permissions object
        if (!$value instanceof UserPermissions) {
            return null;
        }

        //First, pack whether they're a GlobalAdmin
        $result = $packer->pack($value->IsGlobalAdmin);

        //Then give out the GroupPerms length
        $result .= $packer->packArrayHeader(count($value->EventPerms));

        //And then splat the group perms!
        foreach ($value->EventPerms as $event => $perm) {
            $result .=$packer->pack($event)
            . $packer->pack($perm);
        }

        //Give back the result
        return $packer->packExt($this->getType(), $result);
    }

    public function unpackExt(BufferUnpacker $unpacker, int $extLength): UserPermissions
    {
        $result = new UserPermissions();
        //We'll always have an bool for the GlobalAdmin
        $result->IsGlobalAdmin = $unpacker->unpack();
        //Check if we have any EventPerms
        $eventPermCount = $unpacker->unpackArrayHeader();
        //Un-splat the events!
        for (;$eventPermCount > 0;$eventPermCount--) {
            $event = $unpacker->unpack();
            $perm = $unpacker->unpack();
            $result->EventPerms[$event] = $perm;
        }
        //Give back our new Permissions!
        return $result;
    }

    public function getPermEnumeration()
    {
        return array(
            'IsGlobalAdmin' => $this->IsGlobalAdmin,
            'EventPerms' => array_map(function ($perm) {
                return $perm->getPermEnumeration();
            }, $this->EventPerms)
        );
    }
}
