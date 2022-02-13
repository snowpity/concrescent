<?php

namespace CM3_Lib\util;

//Don't use this one until the patch to set value directly is added
#use Cruxinator\BitMask\BitMask ;

class PermEvent extends Bitmask
{
    public const CannotApply          = 1;
    public const Attendee_View        = 2;
    public const Attendee_Edit        = 4;
    public const Attendee_Export      = 8;
    public const Attendee_Manage      = 16;
    public const Attendee_Refund      = 32;
    public const Badge_Stats          = 64;
    public const Badge_Checkin        = 128;
    public const Badge_PrintOneOff    = 256;
    public const Badge_ManageFormat   = 512;
    public const Location_Manage      = 1024;
    public const Payment_View         = 2048;
    public const Payment_CreateCancel = 4096;
    public const Payment_Edit         = 8192;
    public const Manage_Banlist       = 16384;
    public const Manage_Users         = 32768;
    public const Staff_View           = 65536;
    public const Staff_Review         = 131072;
    public const Staff_Edit           = 262144;
    public const Staff_Export         = 524288;
    public const Staff_Manage         = 1048576;
    public const Filestore_Manage     = 2097152;

    // public const Reserved2            = 4194304;
    // public const Reserved3            = 8388608;
    // public const Reserved4            = 16777216;
    // public const Reserved5            = 33554432;
    // public const Reserved6            = 67108864;
    // public const Reserved7            = 134217728;
    public const Badge_Ice            = 268435456;
    public const Contact_Full         = 536870912;

    public const EventAdmin           = 1073741824;
    public const GlobalAdmin          = 2147483648;
    public const NoPermission         = 0;
}
