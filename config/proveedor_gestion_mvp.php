<?php

/**
 * Matriz MVP de gestión de usuarios / roles / menú (plataforma).
 * Fuente operativa: docs/context/platform-users-roles.md
 */
return [

    /** Roles que gerente/supervisor pueden asignar al crear/editar usuarios de la empresa */
    'roles_asignables_empresa' => [
        'SUPERVISOR',
        'VENTAS',
        'AUXILIAR',
    ],

    /** Roles que pueden listar/crear/editar usuarios del proveedor */
    'roles_gestion_usuarios_cru' => [
        'GERENTE',
        'SUPERVISOR',
    ],

    /** Roles que pueden borrar usuarios (nunca al PRINCIPAL; admin siempre puede) */
    'roles_gestion_usuarios_delete' => [
        'GERENTE',
    ],

];
