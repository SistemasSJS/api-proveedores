<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 *     schema="Producto",
 *     required={"nombre", "proveedor_id"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nombre", type="string", example="Cemento gris 50kg"),
 *     @OA\Property(property="modelo_codigo", type="string", example="MX-458G-9"),
 *     @OA\Property(property="descripcion", type="string", example="Saco de cemento gris para construcción"),
 *     @OA\Property(property="sku", type="string", example="CMG-50"),
 *     @OA\Property(property="categoria_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="marca_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="linea_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="proveedor_id", type="integer", example=3),
 *     @OA\Property(property="unidad_medida_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="grupo_id", type="integer", nullable=true, example=4),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-20T14:30:00Z"),
 *     @OA\Property(
 *         property="categoria",
 *         ref="#/components/schemas/Categoria"
 *     ),
 *     @OA\Property(
 *         property="marca",
 *         ref="#/components/schemas/Marca"
 *     ),
 *     @OA\Property(
 *         property="linea",
 *         ref="#/components/schemas/Linea"
 *     ),
 *     @OA\Property(
 *         property="proveedor",
 *         ref="#/components/schemas/Proveedor"
 *     ),
 *     @OA\Property(
 *         property="unidad_medida",
 *         ref="#/components/schemas/UnidadMedida"
 *     ),
 *     @OA\Property(
 *         property="grupo",
 *         ref="#/components/schemas/Grupo"
 *     ),
 *     @OA\Property(
 *         property="imagenes",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Imagen")
 *     )
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
        'nombre',
        'modelo_codigo',
        'descripcion',
        'sku',
        'categoria_id',
        'marca_id',
        'linea_id',
        'proveedor_id',
        'unidad_medida_id',
        'grupo_id',
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
            'proveedor',
            'unidad_medida',
            'grupo',
            'imagenes',
            'categoria',
            'marca',
            'linea',
        ];
    }

    /**
     * Relación con el modelo Proveedor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Relación con el modelo UnidadMedida.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function unidad_medida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    /**
     * Relación con el modelo Grupo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    /**
     * Relación con el modelo Imagen (uno a muchos).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function imagenes()
    {
        return $this->hasMany(Imagen::class);
    }

    /**
     * Relación con el modelo Categoria.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Relación con el modelo Marca.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    /**
     * Relación con el modelo Linea.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    /**
     * Filtro por nombre.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    /**
     * Filtro por modelo o código.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filterByModeloCodigo($query, $value)
    {
        return $query->where('modelo_codigo', 'like', "%$value%");
    }

    /**
     * Filtro por descripción.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filterByDescripcion($query, $value)
    {
        return $query->where('descripcion', 'like', "%$value%");
    }

    /**
     * Filtro por SKU.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filterBySku($query, $value)
    {
        return $query->where('sku', 'like', "%$value%");
    }
}
