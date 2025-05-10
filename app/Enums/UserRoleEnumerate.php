<?php

namespace App\Enums;

/**
 * Enumerado de los roles de usuario en la plataforma.
 */
enum UserRoleEnumerate: string
{
    /**
     * Usuario con el rol más alto. Acceso total al sistema.
     */
    case SUPER_ADMIN = 'SUPER_ADMIN';

    /**
     * Usuario administrador. Puede haber más de uno.
     * Tiene privilegios elevados de gestión.
     */
    case ADMIN = 'ADMIN';

    /**
     * Usuario estándar registrado.
     * Acceso a funcionalidades básicas.
     */
    case USUARIO = 'USUARIO';

    /**
     * Usuario no registrado (invitado o visitante).
     * Acceso muy limitado o solo lectura.
     */
    case USUARIO_NO_REGISTRADO = 'USUARIO_NO_REGISTRADO';

    /**
     * Usuario en proceso de registro o con perfil incompleto.
     */
    case USUARIO_CONSTRUCCION = 'USUARIO_CONSTRUCCION';

    /**
     * Usuario proveedor. Acceso a funcionalidades específicas de proveedor.
     */
    case PROVEEDOR = 'PROVEEDOR';
}
