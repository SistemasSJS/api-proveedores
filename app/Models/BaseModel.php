<?php

namespace App\Models;

use App\Traits\AutoSwaggerSchema;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use AutoSwaggerSchema;
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
        foreach ($filters as $filter => $value) {
            if (isset(self::$filters[$filter]) && !is_null($value)) {
                $method = 'filterBy' . ucfirst(self::$filters[$filter]);
                if (method_exists($this, $method)) {
                    $this->$method($query, $value);
                }
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
