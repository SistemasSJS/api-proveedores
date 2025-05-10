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


## PASOS PARA CONFIGURAR SWAGGER EN LARAVEL 12

1. 📦 Instala el paquete
```bash
    composer require "darkaonline/l5-swagger"
```

2. ⚙️ Publica los archivos de configuración
```bash
    php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```
Esto genera:
    config/l5-swagger.php
    Carpeta de documentación en storage/api-docs

3. 🛠️ Configura tu archivo .env
Agrega (si no está ya):
```
    L5_SWAGGER_GENERATE_ALWAYS=true
    L5_SWAGGER_CONST_HOST=http://localhost:8000
```
🔁 L5_SWAGGER_GENERATE_ALWAYS=true genera la documentación cada vez que accedes, útil en desarrollo.


4. 🧾 Anota tu controlador con comentarios Swagger
Aquí te dejo un ejemplo de cómo anotar el método index() de tu ProveedorController:

php
Copiar
Editar
/**
 * @OA\Get(
 *     path="/api/proveedores",
 *     tags={"Proveedores"},
 *     summary="Listar todos los proveedores",
 *     @OA\Response(
 *         response=200,
 *         description="Lista de proveedores",
 *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Proveedor"))
 *     )
 * )
 */
public function index()
{
    return response()->json(Proveedor::all());
}

5. 🧬 Define el schema del modelo
Puedes anotar la clase del modelo Proveedor así:

php
Copiar
Editar
/**
 * @OA\Schema(
 *     title="Proveedor",
 *     description="Modelo de proveedor",
 *     @OA\Xml(name="Proveedor")
 * )
 */
class Proveedor extends Model
{
    /**
     * @OA\Property(example="1")
     * @var int
     */
    public $id;

    /**
     * @OA\Property(example="Proveedor S.A.")
     * @var string
     */
    public $nombre;

    /**
     * @OA\Property(example="proveedor@email.com")
     * @var string
     */
    public $email;

    /**
     * @OA\Property(example="123456789")
     * @var string
     */
    public $telefono;
}

6. 📄 Generar la documentación
php artisan l5-swagger:generate

7. 🌐 Accede a la documentación
Visita en tu navegador:

http://localhost:8000/api/documentation



🔐 Backend (Laravel)
Instalar el paquete de validación:

bash
Copiar
Editar
composer require anhskohbo/no-captcha
Agregar las claves en .env:

env
Copiar
Editar
NOCAPTCHA_SITEKEY=tu_clave_site
NOCAPTCHA_SECRET=tu_clave_secreta
Publicar el config (opcional):

bash
Copiar
Editar
php artisan vendor:publish --provider="Anhskohbo\NoCaptcha\NoCaptchaServiceProvider"
Validar en tu StoreProveedorRequest:

php
Copiar
Editar
public function rules()
{
    return [
        // tus reglas actuales...
        'g-recaptcha-response' => ['required', 'captcha'],
    ];
}
Registrar el provider (si usás Laravel < 8) en config/app.php:

php
Copiar
Editar
'providers' => [
    Anhskohbo\NoCaptcha\NoCaptchaServiceProvider::class,
],