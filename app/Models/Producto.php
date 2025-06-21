<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Producto",
 *     required={"nombre", "catalogo_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="sku", type="string", example="CMG-50"),
 *     @OA\Property(property="nombre", type="string", example="Cemento gris 50kg"),
 *     @OA\Property(property="logo", type="string", format="uri", example="https://misitio.com/logo.png"),
 *     @OA\Property(property="modelo_codigo", type="string", example="MX-458G-9"),
 *     @OA\Property(property="descripcion", type="string", example="Saco de cemento gris para construcción"),
 *     @OA\Property(property="marca_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="linea_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="catalogo_id", type="integer", example=3),
 *     @OA\Property(property="unidad_medida_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-20T14:30:00Z"),
 *     @OA\Property(property="marca", ref="#/components/schemas/Marca"),
 *     @OA\Property(property="linea", ref="#/components/schemas/Linea"),
 *     @OA\Property(property="catalogo", ref="#/components/schemas/Catalogo"),
 *     @OA\Property(property="unidad_medida", ref="#/components/schemas/UnidadMedida"),
 *     @OA\Property(property="imagenes", type="array", @OA\Items(ref="#/components/schemas/Imagen"))
 * )
 */
class Producto extends BaseModel
{
    use HasFactory;

    /**
     * Atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sku',
        'modelo',
        'nombre',
        'descripcion',
        'imagen_principal',
        'activo',
        'stock',
        'precio_base',
        'precio_de_lista',
        'precio_público',
        'precio_mayoreo',
        'precio_con_IVA',
        'precio_sin_IVA',
        'precio_promocional',
        'precio_distribuidor',
        'precio_especial',
        'proveedor_id',
        'categoria_id',
        'marca_id',
        'linea_id',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['categoria', 'marca', 'linea', 'especificaciones', 'imagenes'];

    /**
     * Filtros disponibles para aplicar dinámicamente en consultas.
     * El índice es el nombre del filtro recibido desde el request
     * y el valor corresponde al nombre del método de filtro.
     *
     * Ejemplo: 'nombre' => 'nombre' buscará un método llamado `filterByNombre()`.
     *
     * @var array<string, string>
     */
    protected static $filters = [
        'sku' => 'sku',
        'nombre' => 'nombre',
        'descripcion' => 'descripcion',
        'modelo' => 'modelo',
    ];

    /**
     * Relaciones disponibles para cargar con eager loading.
     *
     * Estas relaciones pueden usarse en `with()` para evitar el problema N+1.
     *
     * @return string[]
     */
    public static function eagerLodable(): array
    {
        return ['categoria', 'marca', 'linea', 'especificaciones', 'imagenes'];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    // // NOTE: Sin uso. el producto solo debe tener una catergoria
    // public function categorias(): BelongsToMany
    // {
    //     return $this->belongsToMany(Categoria::class,  'categoria_producto', 'producto_id', 'categoria_id');
    // }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function especificaciones(): HasMany
    {
        return $this->hasMany(ProductoEspecificacion::class, 'producto_id', 'especificacion_id');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class);
    }

    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class)
            ->withPivot('stock_local', 'precio_local', 'activo')
            ->withTimestamps();
    }

    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    public function filterByCategoriaId($query, $value)
    {
        $ids = explode(',', $value); // Permite recibir múltiples IDs separados por coma
        return $query->whereHas('categorias', function ($q) use ($ids) {
            $q->whereIn('categoria_id', $ids);
        });
    }

    public function filterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    public function filterByDescripcion($query, $value)
    {
        return $query->where('descripcion', 'like', "%$value%");
    }

    public function filterBySku($query, $value)
    {
        return $query->where('sku', 'like', "%$value%");
    }
}
