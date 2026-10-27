<?php

namespace App\Enum;

enum TicketStatus: string
{
    case Open = "open";
    case Pending = "pending";
    case Resolved = "resolved";
    case Closed = "closed";
}
