<?php

namespace App\Enum;

enum MessageDirection: string
{
    case Inbound = "inbound";
    case Outbound = "outbound";
}
