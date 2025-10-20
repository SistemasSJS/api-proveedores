<?php

namespace App\Enums;

enum UserRoleEnumerate: string
{
    case ADMINISTRADOR = 'ADMINISTRADOR';
    case GERENTE = 'GERENTE';
    case SUPERVISOR = 'SUPERVISOR';
    case VENTAS = 'VENTAS';
    case AUXILIAR = 'AUXILIAR';
    case USUARIO = 'USUARIO';
    case CLIENTE = 'CLIENTE';
    case CONSTUCC_APP = 'CONSTRUCC_APP';
}
