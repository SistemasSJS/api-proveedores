<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Proveedor",
 *     title="Proveedor",
 *     description="Modelo de proveedor en el sistema.",
 *     type="object",
 *     required={"nombre", "razon_social", "rfc"},
 *     @OA\Property(property="logo", type="string", example="logos/materiales-del-pacifico.png", description="Ruta al logo del proveedor"),
 *     @OA\Property(property="id", type="integer", example=1, description="ID del proveedor"),
 *     @OA\Property(property="nombre_comercial", type="string", example="Materiales del Pacífico S.A. de C.V.", description="Nombre del proveedor"),
 *     @OA\Property(property="rfc", type="string", example="MAPC800101ABC", description="RFC del proveedor"),
 *     @OA\Property(property="razon_social", type="string", example="Materiales del Pacífico", description="Razón social del proveedor"),
 *     @OA\Property(property="telefono", type="string", example="6679876543", description="Teléfono del proveedor"),
 *     @OA\Property(property="correo", type="string", example="ventas@materialespacifico.mx", description="Correo electrónico del proveedor"),
 *     @OA\Property(property="estado", type="string", example="Sinaloa", description="Estado donde se encuentra el proveedor"),
 *     @OA\Property(property="municipio", type="string", example="Culiacán", description="Municipio donde se encuentra el proveedor"),
 *     @OA\Property(property="codigo_postal", type="string", example="80000", description="Código postal del proveedor"),
 *     @OA\Property(property="sitio_web", type="string", example="http://materialespacifico.mx", description="Sitio web del proveedor"),
 *     @OA\Property(property="fecha_registro", type="string", format="date-time", example="2025-04-17T12:00:00Z", description="Fecha de registro del proveedor"),
 *     @OA\Property(property="estatus", type="string", example="activo", description="Estatus del proveedor (activo o inactivo)"),
 *     @OA\Property(property="validado_por", type="integer", example=1, description="ID del usuario que validó el proveedor"),
 *     @OA\Property(property="notas", type="string", example="Proveedor verificado", description="Notas adicionales sobre el proveedor"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Fecha de creación del registro"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Fecha de la última actualización del registro")
 * )
 */
class Proveedor extends BaseModel
{
    use HasFactory;
    protected $table = "proveedores";

    protected $fillable = [
        'logo',
        'nombre_comercial',
        'razon_social',
        'rfc',
        'tipo_persona',
        'email',
        'telefono',
        'sitio_web',
        'direccion_fiscal',
        'estado',
        'municipio',
        'codigo_postal',
        'contacto_nombre',
        'contacto_telefono',
        'contacto_email',
        'estatus',
        'fecha_registro',
        'validado_por',
        'user_id',
        'notas',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
    ];


    // Filtros disponibles para este modelo
    protected static $filters = [
        'razon_social' => 'razon_social',
        'nombre_comercial' => 'nombre_comercial',
        'email' => 'email',
    ];

    // Filtro específico para 'razon_social'
    public function filterByRazonSocial($query, $value)
    {
        return $query->where('razon_social', 'like', "%$value%");
    }

    // Filtro específico para 'nombre_comercial'
    public function filterByNombreComercial($query, $value)
    {
        return $query->where('nombre_comercial', 'like', "%$value%");
    }

    // Filtro específico para 'email'
    public function filterByEmail($query, $value)
    {
        return $query->where('email', 'like', "%$value%");
    }

    // Relaciones
    public function sucursales()
    {
        return $this->hasMany(Sucursal::class);
    }


    public function productos()
    {
        return $this->hasMany(Producto::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validador()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }
}
