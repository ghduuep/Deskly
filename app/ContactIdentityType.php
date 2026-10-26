<?php

namespace App;

enum ContactIdentityType: string
{
    case Email = "email";
    case Whatsapp = "whatsapp";
    case Web = "web";
}
