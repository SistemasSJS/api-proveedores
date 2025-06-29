<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait para modelos que genera schemas automáticamente
 */
trait AutoSwaggerSchema
{
    /**
     * Genera schema Swagger basado en fillable, casts y relaciones
     */
    public static function getSwaggerSchema()
    {
        $instance = new static();
        $modelName = class_basename(static::class);

        $fillable = $instance->getFillable();
        $casts = $instance->getCasts();
        $hidden = $instance->getHidden();
        $relations = static::detectModelRelations();

        return [
            'model' => $modelName,
            'properties' => static::generateSchemaProperties($fillable, $casts, $hidden),
            'relations' => $relations
        ];
    }

    /**
     * Detecta relaciones del modelo automáticamente
     */
    private static function detectModelRelations()
    {
        $relations = [];
        $reflection = new \ReflectionClass(static::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $methodName = $method->getName();

            // Skip métodos de Eloquent
            if (in_array($methodName, ['toArray', 'toJson', 'save', 'delete', 'update'])) {
                continue;
            }

            // Detectar relaciones por nombre y convención
            if (
                preg_match('/^[a-z][a-zA-Z]*$/', $methodName) &&
                $method->getNumberOfParameters() === 0
            ) {

                // belongsTo (singular)
                if (in_array($methodName, ['proveedor', 'categoria', 'marca', 'user', 'cliente'])) {
                    $relations[] = [
                        'name' => $methodName,
                        'type' => 'belongsTo',
                        'model' => ucfirst($methodName)
                    ];
                }

                // hasMany (plural)
                if (
                    str_ends_with($methodName, 's') &&
                    in_array($methodName, ['productos', 'categorias', 'usuarios', 'imagenes'])
                ) {
                    $relations[] = [
                        'name' => $methodName,
                        'type' => 'hasMany',
                        'model' => ucfirst(rtrim($methodName, 's'))
                    ];
                }
            }
        }

        return $relations;
    }

    /**
     * Genera propiedades del schema
     */
    private static function generateSchemaProperties($fillable, $casts, $hidden)
    {
        $properties = [
            'id' => [
                'type' => 'integer',
                'example' => 1,
                'description' => 'ID único del registro'
            ]
        ];

        foreach ($fillable as $field) {
            if (in_array($field, $hidden)) continue;

            $type = static::inferPropertyType($field, $casts);
            $properties[$field] = [
                'type' => $type['type'],
                'format' => $type['format'] ?? null,
                'example' => static::generateExample($field, $type),
                'description' => static::generateDescription($field)
            ];
        }

        // Timestamps
        if (!in_array('created_at', $hidden)) {
            $properties['created_at'] = [
                'type' => 'string',
                'format' => 'datetime',
                'example' => '2024-01-01T00:00:00.000000Z'
            ];
        }

        if (!in_array('updated_at', $hidden)) {
            $properties['updated_at'] = [
                'type' => 'string',
                'format' => 'datetime',
                'example' => '2024-01-01T00:00:00.000000Z'
            ];
        }

        return $properties;
    }

    /**
     * Infiere tipo de propiedad
     */
    private static function inferPropertyType($field, $casts)
    {
        // Usar cast si existe
        if (isset($casts[$field])) {
            return static::castToSwaggerType($casts[$field]);
        }

        // Inferir por nombre
        if (str_ends_with($field, '_id')) return ['type' => 'integer'];
        if (Str::endsWith($field, 'needles')($field, ['_at', '_time'])) return ['type' => 'string', 'format' => 'datetime'];
        if (str_contains($field, 'email')) return ['type' => 'string', 'format' => 'email'];
        if (str_contains($field, 'password')) return ['type' => 'string', 'format' => 'password'];
        if (Str::endsWith($field, ['precio', 'costo', 'total'])) return ['type' => 'number', 'format' => 'float'];
        if (Str::endsWith($field, ['activo', 'enabled', 'visible'])) return ['type' => 'boolean'];

        return ['type' => 'string'];
    }

    /**
     * Convierte cast de Laravel a tipo Swagger
     */
    private static function castToSwaggerType($cast)
    {
        $typeMap = [
            'boolean' => ['type' => 'boolean'],
            'bool' => ['type' => 'boolean'],
            'integer' => ['type' => 'integer'],
            'int' => ['type' => 'integer'],
            'float' => ['type' => 'number', 'format' => 'float'],
            'double' => ['type' => 'number', 'format' => 'float'],
            'decimal' => ['type' => 'number', 'format' => 'float'],
            'string' => ['type' => 'string'],
            'array' => ['type' => 'array'],
            'json' => ['type' => 'object'],
            'object' => ['type' => 'object'],
            'collection' => ['type' => 'array'],
            'date' => ['type' => 'string', 'format' => 'date'],
            'datetime' => ['type' => 'string', 'format' => 'datetime'],
            'timestamp' => ['type' => 'string', 'format' => 'datetime']
        ];

        return $typeMap[$cast] ?? ['type' => 'string'];
    }

    /**
     * Genera ejemplo para el campo
     */
    private static function generateExample($field, $type)
    {
        switch ($type['type']) {
            case 'integer':
                if (str_ends_with($field, '_id')) return 1;
                if (Str::contains($field, ['stock', 'cantidad'])) return 100;
                if (Str::contains($field, ['codigo', 'numero'])) return 123;
                return 1;

            case 'number':
                if (Str::contains($field, ['precio', 'cost'])) return 150.50;
                if (Str::contains($field, ['porcentaje', 'percent'])) return 15.5;
                return 10.5;

            case 'boolean':
                return true;

            case 'string':
                if ($type['format'] === 'email') return 'usuario@example.com';
                if ($type['format'] === 'password') return 'password123';
                if ($type['format'] === 'datetime') return '2024-01-01T00:00:00.000000Z';
                if ($type['format'] === 'date') return '2024-01-01';

                // Ejemplos por nombre de campo
                if (str_contains($field, 'nombre')) return 'Ejemplo Nombre';
                if (str_contains($field, 'descripcion')) return 'Descripción de ejemplo';
                if (str_contains($field, 'codigo')) return 'COD-001';
                if (str_contains($field, 'sku')) return 'SKU-001';
                if (str_contains($field, 'telefono')) return '6221234567';
                if (str_contains($field, 'direccion')) return 'Calle Ejemplo #123';
                if (str_contains($field, 'ciudad')) return 'Los Mochis';
                if (str_contains($field, 'estado')) return 'Sinaloa';

                return 'ejemplo';

            case 'array':
                return [];

            case 'object':
                return new \stdClass();

            default:
                return null;
        }
    }

    /**
     * Genera descripción para el campo
     */
    private static function generateDescription($field)
    {
        $descriptions = [
            'nombre' => 'Nombre del registro',
            'descripcion' => 'Descripción detallada',
            'email' => 'Dirección de correo electrónico',
            'telefono' => 'Número de teléfono',
            'direccion' => 'Dirección física',
            'activo' => 'Estado activo/inactivo',
            'visible' => 'Visibilidad del elemento',
            'stock' => 'Cantidad en inventario',
            'precio_base' => 'Precio base del producto',
            'precio_de_lista' => 'Precio de lista',
            'precio_publico' => 'Precio público',
            'sku' => 'Código SKU único',
            'codigo_interno' => 'Código interno del sistema',
            'created_at' => 'Fecha de creación',
            'updated_at' => 'Fecha de última actualización',
            'proveedor_id' => 'ID del proveedor asociado',
            'categoria_id' => 'ID de la categoría asociada',
            'marca_id' => 'ID de la marca asociada'
        ];

        return $descriptions[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }
}
