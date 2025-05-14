<?php

namespace App\Traits;

trait Filterable
{
    protected static $filters = [];

    /**
     * Aplicar filtros con OR encadenado.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            if (!is_null($value)) {
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
