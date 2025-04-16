<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


abstract class BaseModel extends Model {
   public $timestamps = true;

   
    public static function filter($query, $filters)
    {
        foreach ($filters as $filter => $value) {
            if (method_exists(self::class, $filter)) {
                self::$filter($query, $value);
            }
        }
    }
} 