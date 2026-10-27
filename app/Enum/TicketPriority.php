<?php

namespace App\Enum;

enum TicketPriority: string
{
    case Low = "low";
    case Normal = "normal";
    case High = "high";
    case Urgent = "urgent";
}
