<?php

namespace App\Enums;

enum UserRoleEnumerate: string
{
    case ADMIM = 'admin';
    case PROVEEDOR = 'proveedor';
    case COMMON_USER = 'common_user'; // usuario comun con cuenta registrada 
    case PUBLIC_USER = 'public_user'; // exclusivo para ususarios que no tienen registro
}
