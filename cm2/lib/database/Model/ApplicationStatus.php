<?php

namespace App\Database\Model;

enum ApplicationStatus: string
{
    case Submitted = 'Submitted';
    case Cancelled = 'Cancelled';
    case Accepted = 'Accepted';
    case Waitlisted = 'Waitlisted';
    case Rejected = 'Rejected';
}
