<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    public $timestamps = true;

    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    protected static $filters = [];

    /**
     * Aplicar filtros con OR encadenado.
     *
     * @param $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, array $filters)
    {
        // Asegurarse de que se apliquen los filtros de manera encadenada con OR
        foreach ($filters as $key => $value) {
            if (!is_null($value)) {
                // Se utiliza orWhere para que las condiciones se apliquen con OR
                $query->orWhere($key, 'like', "%$value%");
            }
        }

        return $query;
    }

    /**
     * Obtener los filtros definidos en la clase.
     *
     * @return array
     */
    public static function getFilters(): array
    {
        return array_values(static::$filters);
    }
}
