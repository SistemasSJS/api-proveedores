# make-resource

Genera automáticamente la estructura completa de un nuevo recurso en la API **api-proveedores** siguiendo los patrones definidos para Models y Controllers.

## Uso

```
/make-resource NombreRecurso
```

Ejemplo:

```
/make-resource Presupuesto
```

## Instrucciones para el agente

Cuando se ejecute este comando debes:

1. Crear el **Model**
   - Ruta: `app/Models/NombreRecurso.php`
   - Extender `BaseModel`
   - Incluir:

   ```php
   protected $connection = 'mysql5';
   public $timestamps = true;

   protected $fillable = [];
   protected $casts = [];
   protected $hidden = ['created_at','updated_at'];

   protected static $filters = [];
   ```

2. Crear **Migration**
   - `database/migrations/create_nombre_recursos_table.php`
   - Incluir:

   ```php
   $table->id();
   $table->timestamps();
   ```

3. Crear **Requests**
   - `StoreNombreRecursoRequest`
   - `UpdateNombreRecursoRequest`

   Ruta:

   ```
   app/Http/Requests/
   ```

4. Crear **Resources**
   - `NombreRecursoResource`
   - `NombreRecursoCollection`

   Ruta:

   ```
   app/Http/Resources/
   ```

5. Crear **Controller**
   - Ruta:

   ```
   app/Http/Controllers/Api/NombreRecursoController.php
   ```

   - Métodos:

   ```
   index
   store
   show
   update
   destroy
   ```

6. En el método **index** implementar:

   ```php
   $filters = $request->only(Model::getFilters());

   $query = Model::query()
       ->with(Model::eagerLodable())
       ->filter($filters)
       ->orderBy($sortBy, $order)
       ->paginate($perPage);
   ```

7. Usar respuestas del sistema:

   ```php
   return $this->success($data, 'Operación exitosa.');
   ```

8. Cuando se creen o actualicen registros usar:

   ```php
   $model->fresh(Model::eagerLodable());
   ```

9. Si se modifican múltiples tablas usar:

   ```php
   DB::transaction(function () {
       // lógica
   });
   ```

10. Registrar las rutas en:

```
routes/segmented/
```

usando:

```php
Route::apiResource('recurso', RecursoController::class);
```
