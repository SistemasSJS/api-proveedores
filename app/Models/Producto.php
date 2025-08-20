<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'imagen_principal', // aun no se incluyen imgs
        //campos de importacion
        'codigo_interno',
        'proveedor_id',
        'nombre',
        'descripcion',
        'marca_id',
        'categoria_id',
        'subcategoria_id',
        'precio_base',
        'precio_mayoreo',
        'precio_menudeo',
        // otros campos
        'modelo',
        'activo',
        'stock',
        'destacado',
        'principal',
        'estatus',
    ];

    /**
     * The relations to eager load on every query.
     */
    protected $with = ['proveedor', 'categoria', 'subcategoria', 'marca', 'linea', 'especificaciones', 'imagenes'];

    /**
     * Filtros disponibles para aplicar dinámicamente en consultas.
     * El índice es el nombre del filtro recibido desde el request
     * y el valor corresponde al nombre del método de filtro.
     *      Ejemplo: 'nombre' => 'nombre' buscará un método llamado `filterByNombre()`.
     * @var array<string, string>
     */
    protected static $filters = [
        'nombre'       => 'Nombre',
        'descripcion'  => 'Descripcion',
        'sku'          => 'Sku',
        'codigo'          => 'Codigo',
        'categoria_id' => 'CategoriaId',
        'subcategoria_id' => 'SubCategoriaId',
        'proveedor_id' => 'proveedorId',
        'marca_id' => 'marcaId',
    ];

    protected $casts = [
        'tags' => 'array',
        'precio_base' => 'float',
        'precio_mayoreo' => 'float',
        'precio_menudeo' => 'float',
        'principal' => 'boolean',
        'destacado' => 'boolean',
        'activo' => 'boolean',
    ];


    /**
     * Relaciones disponibles para cargar con eager loading.
     * Estas relaciones pueden usarse en `with()` para evitar el problema N+1.
     * @return string[]
     */
    public static function eagerLodable(): array
    {
        return [
            'marca',
            'categoria',
            'subcategoria',
            'unidad_medida',
            'especificaciones',
            'imagenes',
        ];
    }

    public function unidad_medida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function pedidoProductos()
    {
        return $this->hasMany(PedidoDetalle::class);
    }

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

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function especificaciones(): HasMany
    {
        return $this->hasMany(ProductoEspecificacion::class);
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

    public function requisicionDetalles(): HasMany
    {
        return $this->hasMany(RequisicionDetalle::class);
    }

    public function getStockEnSucursal($sucursalId)
    {
        $pivotData = $this->sucursales->firstWhere('id', $sucursalId)?->pivot;
        return $pivotData ? $pivotData->stock_local : 0;
    }

    public function getPrecioEnSucursal($sucursalId)
    {
        $pivotData = $this->sucursales->firstWhere('id', $sucursalId)?->pivot;
        return $pivotData ? $pivotData->precio_local : $this->precio_base;
    }


    public function scopeDelProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }


    /*****************************************
     * Filtros por id de modelo realcionado
     *****************************************/

    public function filterByCategoriaId($query, $value)
    {
        $ids = array_filter(explode(',', $value));
        if (empty($ids)) return $query;
        return $query->whereIn('categoria_id', $ids);
    }

    public function filterBySubCategoriaId($query, $value)
    {
        $ids = array_filter(explode(',', $value));
        if (empty($ids)) return $query;
        return $query->whereIn('subcategoria_id', $ids);
    }

    public function filterByMarcaId($query, $value)
    {
        $ids = explode(',', $value);
        if (empty($ids)) return $query;
        return $query->whereIn('marca_id', $ids);
    }


    /*****************************************
     * Filtros STR
     *****************************************/

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
    public function filterByCodigo($query, $value)
    {
        return $query->where('codigo_interno', 'like', "%$value%");
    }
}
