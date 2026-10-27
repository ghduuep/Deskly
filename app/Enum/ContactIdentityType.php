<?php

namespace App\Enum;

enum ContactIdentityType: string
{
    case Email = "email";
    case Whatsapp = "whatsapp";
    case Web = "web";
}
