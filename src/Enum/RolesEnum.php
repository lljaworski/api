<?php

namespace App\Enum;

enum RolesEnum: string
{
    case ROLE_ADMIN = 'Administrator';
    case ROLE_USER = 'User';
    case ROLE_B2B = 'B2B Manager';
}
