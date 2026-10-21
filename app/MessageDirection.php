<?php

namespace App;

enum MessageDirection: string
{
    case Inbound = "inbound";
    case Outbound = "outbound";
}
