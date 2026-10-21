<?php

namespace App;

enum MessageType: string
{
    case Reply = "reply";
    case Internal = "internal";
}
