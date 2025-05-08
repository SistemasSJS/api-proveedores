<?php

namespace App\Enums;

/**
 * Enumerado de los tipos de usuarios existentes en la plataforma.
 */
enum UserRoleEnumerate: string
{
    /**
     * Usuario perteneciente al gerente general de la empresa.
     * Rol único y con máximos privilegios.
     */
    case SUPER_ADMIN = 'super_admin';

    /**
     * Usuario administrador. Puede haber más de uno.1
     * Tiene privilegios elevados para la gestión general.
     */
    case ADMIN = 'admin';

    /**
     * Usuario registrado estándar.
     * Acceso limitado a funcionalidades básicas de la plataforma.
     */
    case USUARIO = 'usuario';

    /**
     * Usuario no registrado.
     * Acceso restringido; posiblemente visitante o invitado.
     */
    case USUARIO_NO_REGISTRADO = 'usuario_no_registrado';

    /**
     * Usuario en proceso de registro o con perfil incompleto.
     */
    case USUARIO_CONSTRUCCION = 'usuario_construccion';

    /**
     * Usuario proveedor.
     * Tiene acceso a funcionalidades específicas para proveedores.
     */
    case PROVEEDOR = 'proveedor';
}
