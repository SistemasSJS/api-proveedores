<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;


/**
 * EJEMPLO DE USO EN UN CONTROLADOR:
 * 
 * <?php
 * 
 * namespace App\Http\Controllers;
 * 
 * use App\Traits\AutoSwaggerDocumentation;
 * 
 * class ProductoController extends Controller 
 * {
 *     use AutoSwaggerDocumentation;
 * 
 *     // Tus métodos normales...
 *     public function index() {}
 *     public function store() {}
 *     // etc...
 * }
 */



/**
 * Trait para generar documentación Swagger automáticamente
 * Solo agregar este trait a tus controladores
 */
trait AutoSwaggerDocumentation
{
  /**
   * Genera automáticamente la documentación para un controlador resource
   */
  public static function getSwaggerDocumentation()
  {
    $controllerName = class_basename(static::class);
    $resourceName = str_replace('Controller', '', $controllerName);
    $tag = $resourceName;
    $modelName = $resourceName;

    return [
      'tag' => $tag,
      'model' => $modelName,
      'routes' => static::generateResourceRoutes($resourceName, $tag, $modelName)
    ];
  }

  /**
   * Genera rutas estándar de un resource controller
   */
  private static function generateResourceRoutes($resource, $tag, $model)
  {
    $resourceLower = strtolower($resource);
    $resourcePlural = $resourceLower . 's'; // Simplificado para este ejemplo

    return [
      'index' => [
        'method' => 'GET',
        'path' => "/{$resourcePlural}",
        'summary' => "Listar {$resourcePlural}",
        'description' => "Obtiene lista paginada de {$resourcePlural} con filtros opcionales",
        'responses' => [
          '200' => "Lista de {$resourcePlural} obtenida exitosamente"
        ]
      ],
      'store' => [
        'method' => 'POST',
        'path' => "/{$resourcePlural}",
        'summary' => "Crear {$resourceLower}",
        'description' => "Crea un nuevo {$resourceLower}",
        'requestBody' => true,
        'responses' => [
          '201' => "{$resource} creado exitosamente",
          '422' => 'Errores de validación'
        ]
      ],
      'show' => [
        'method' => 'GET',
        'path' => "/{$resourcePlural}/{id}",
        'summary' => "Obtener {$resourceLower}",
        'description' => "Obtiene un {$resourceLower} específico por ID",
        'parameters' => [
          [
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'type' => 'integer'
          ]
        ],
        'responses' => [
          '200' => "{$resource} encontrado exitosamente",
          '404' => "{$resource} no encontrado"
        ]
      ],
      'update' => [
        'method' => 'PUT',
        'path' => "/{$resourcePlural}/{id}",
        'summary' => "Actualizar {$resourceLower}",
        'description' => "Actualiza un {$resourceLower} existente",
        'parameters' => [
          [
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'type' => 'integer'
          ]
        ],
        'requestBody' => true,
        'responses' => [
          '200' => "{$resource} actualizado exitosamente",
          '404' => "{$resource} no encontrado",
          '422' => 'Errores de validación'
        ]
      ],
      'destroy' => [
        'method' => 'DELETE',
        'path' => "/{$resourcePlural}/{id}",
        'summary' => "Eliminar {$resourceLower}",
        'description' => "Elimina un {$resourceLower} del sistema",
        'parameters' => [
          [
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'type' => 'integer'
          ]
        ],
        'responses' => [
          '204' => "{$resource} eliminado exitosamente",
          '404' => "{$resource} no encontrado"
        ]
      ]
    ];
  }
}
