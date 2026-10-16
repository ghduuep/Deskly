<?php

namespace App;

enum TicketPriority: string
{
    case Low = "low";
    case Normal = "normal";
    case High = "high";
    case Urgent = "urgent";
}
