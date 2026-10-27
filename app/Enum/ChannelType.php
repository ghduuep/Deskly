<?php

namespace App\Enum;

enum ChannelType: string
{
    case Web = "web";
    case Whatsapp = "whatsapp";
    case Email = "email";
}
