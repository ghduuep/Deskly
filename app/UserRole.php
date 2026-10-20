<?php

namespace App;

enum UserRole: string
{
    case Customer = 'customer';
    case Agent = 'agent';
    case Admin = 'admin';
}
