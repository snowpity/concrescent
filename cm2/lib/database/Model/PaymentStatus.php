<?php

namespace App\Database\Model;

enum PaymentStatus: string
{
    case Incomplete = 'Incomplete';
    case Cancelled = 'Cancelled';
    case Rejected = 'Rejected';
    case Completed = 'Completed';
    case Refunded = 'Refunded';
}
