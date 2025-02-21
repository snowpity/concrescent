<?php

namespace App\Database\Model;

enum NameOnBadge: string
{
    case FandomLargeRealSmall = 'Fandom Name Large, Real Name Small';
    case RealLargeFandomSmall = 'Real Name Large, Fandom Name Small';
    case FandomOnly = 'Fandom Name Only';
    case RealOnly = 'Real Name Only';
}
