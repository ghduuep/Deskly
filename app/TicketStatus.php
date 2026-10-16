<?php

namespace App;

enum TicketStatus: string
{
    case Open = "open";
    case Pending = "pending";
    case Resolved = "resolved";
    case Closed = "closed";
}
