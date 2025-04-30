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

    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            $method = 'filter' . ucfirst($key);

            if (method_exists($this, $method)) {
                $this->{$method}($query, $value);
            }
        }

        return $query;
    }
}
