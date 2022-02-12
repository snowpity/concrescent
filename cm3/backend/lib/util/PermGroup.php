<?php

namespace CM3_Lib\util;

//Don't use this one until the patch to set value directly is added
#use Cruxinator\BitMask\BitMask ;

class PermGroup extends Bitmask
{
    public const Submission_View         = 1;
    public const Submission_ReviewAssign = 2;
    public const Submission_Edit         = 4;
    public const Submission_Export       = 8;
    public const Submission_Refund       = 16;
    public const Badge_Manage            = 32;
    public const Badge_View              = 64;
    public const Badge_Edit              = 128;
    public const Badge_Delete            = 256;

    public const NoPermission            = 0;
}
