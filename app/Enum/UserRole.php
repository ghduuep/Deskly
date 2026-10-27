<?php

namespace App\Enum;

enum UserRole: string
{
    case Customer = "customer";
    case Agent = "agent";
    case Admin = "admin";
}
