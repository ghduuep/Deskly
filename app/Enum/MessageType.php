<?php

namespace App\Enum;

enum MessageType: string
{
    case Reply = "reply";
    case Internal = "internal";
}
