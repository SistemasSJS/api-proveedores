# Proveedores API Full Rest

_Aplicacion para manejo de catalago de proveedores._

## Comandos para inicializar configuracion


```Bash
    # instalacion de dependencias  
    composer install
    npm install

    # Generacion de la APP KEY en el archivo .env
    php artisan key:generate
   
    # Ejecutar migraciones
    php artisan migrate --seed

```

# Configuracion de modelos de datos basicos
## 1. Crear modelos con sus respectivas migraciones 

```Bash
    # MODEL Proveedor
    php artisan make:model Proveedor -m

    # MODEL Producto    
    php artisan make:model Producto -m

    # Migracion a DB
    php artisan migrate

```
## Generar ModelsClass

## Generar Controladores 

```Bash
    php artisan make:controller ProveedorController --api
    php artisan make:controller ProductoController --api
```

# Generar rutas en /routes/api.php

```Bash
    use App\Http\Controllers\ProveedorController;
    use App\Http\Controllers\ProductoController;

    Route::apiResource('proveedores', ProveedorController::class);
    Route::apiResource('productos', ProductoController::class);
```


