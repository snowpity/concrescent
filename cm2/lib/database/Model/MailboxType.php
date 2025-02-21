<?php

namespace App\Database\Model;

enum MailboxType: string
{
    case Mailbox = 'Mailbox, With Forwarding';
    case MailboxNoForward = 'Mailbox, No Forwarding';
    case ForwardOnly = 'Forwarding Only';
}
