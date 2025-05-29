<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 *     schema="Producto",
 *     required={"nombre", "catalogo_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Cemento gris 50kg"),
 *     @OA\Property(property="modelo_codigo", type="string", example="MX-458G-9"),
 *     @OA\Property(property="descripcion", type="string", example="Saco de cemento gris para construcción"),
 *     @OA\Property(property="sku", type="string", example="CMG-50"),
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
        'catalogo_id',
        'nombre',
        'descripcion',
        'sku',
        'modelo_codigo',
        'marca_id',
        'linea_id',
        'unidad_medida_id',
    ];

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
        'nombre' => 'nombre',
        'modelo_codigo' => 'modelo_codigo',
        'descripcion' => 'descripcion',
        'sku' => 'sku',
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
        return [
            'catalogo',
            'unidad_medida',
            // 'imagenes',
            'marca',
            'categorias',
            'linea',
        ];
    }

    public function catalogo()
    {
        return $this->belongsTo(Catalogo::class);
    }

    public function unidad_medida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function imagenes()
    {
        return $this->hasMany(Imagen::class);
    }


    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_producto');
    }

    public function filterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    public function filterByModeloCodigo($query, $value)
    {
        return $query->where('modelo_codigo', 'like', "%$value%");
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
