<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Support\Str;

class ProductoFilters
{
  public static function nombre(): Filter
  {
    return new class implements Filter {
      public function __invoke(Builder $query, $value, string $property)
      {
        $query->where('nombre', 'like', '%' . $value . '%');
      }
    };
  }

  public static function descripcion(): Filter
  {
    return new class implements Filter {
      public function __invoke(Builder $query, $value, string $property)
      {
        $query->where('descripcion', 'like', '%' . $value . '%');
      }
    };
  }

  public static function codigo(): Filter
  {
    return new class implements Filter {
      public function __invoke(Builder $query, $value, string $property)
      {
        $query->where('codigo_interno', 'like', '%' . $value . '%');
      }
    };
  }

  public static function multiId(string $column): Filter
  {
    return new class($column) implements Filter {
      public function __construct(protected string $column) {}

      public function __invoke(Builder $query, $value, string $property)
      {
        $ids = array_filter(explode(',', $value));
        if (!empty($ids)) {
          $query->whereIn($this->column, $ids);
        }
      }
    };
  }
}
