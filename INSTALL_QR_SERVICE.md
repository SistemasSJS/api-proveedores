# Instalación del Servicio de Extracción de QR de Constancia Fiscal

## Descripción
Este servicio extrae automáticamente los datos fiscales de la constancia de situación fiscal del SAT leyendo el código QR del PDF y consultando la información en la página del validador del SAT.

## Dependencias Requeridas

### 1. Instalar librería para parsear PDF

```bash
composer require smalot/pdfparser
```

Esta librería permite extraer texto e información de archivos PDF.

### 2. (Opcional) Instalar librería de HTTP Client

Laravel ya incluye `guzzlehttp/guzzle`, pero si no está disponible:

```bash
composer require guzzlehttp/guzzle
```

### 3. (Opcional) Para lectura avanzada de QR desde imágenes

Si el QR no se puede extraer del texto del PDF, se puede usar:

**Opción A: ZBar (Recomendado para producción)**
```bash
# En Ubuntu/Debian
sudo apt-get install zbar-tools

# En CentOS/RHEL
sudo yum install zbar

# En macOS
brew install zbar

# En Windows
# Descargar desde: http://zbar.sourceforge.net/
```

**Opción B: Imagick para extraer imágenes del PDF**
```bash
# PHP Imagick extension
sudo apt-get install php-imagick
# o
sudo pecl install imagick
```

## Configuración

### 1. Verificar que el servicio está registrado

El servicio `ConstanciaFiscalService` se inyecta automáticamente en el controlador. No requiere registro adicional en el service provider.

### 2. Verificar permisos de archivos

Asegúrate de que el directorio de almacenamiento privado tenga permisos de lectura:

```bash
chmod -R 755 storage/app/private/constancias
```

### 3. Variables de entorno (opcional)

Si deseas configurar timeouts o ajustes del servicio:

```env
# .env
SAT_TIMEOUT=30
SAT_MAX_RETRIES=3
```

## Uso

El servicio se ejecuta automáticamente cuando un proveedor sube su constancia fiscal:

1. El usuario sube el PDF de la constancia fiscal
2. El sistema guarda el archivo
3. El servicio `ConstanciaFiscalService` extrae el QR del PDF
4. Consulta la página del SAT con la URL del QR
5. Parsea los datos fiscales del HTML
6. Retorna los datos al frontend para autocompletar el formulario

## Flujo de Datos

```
PDF Uploaded → Save to Storage → Extract QR → Query SAT Website → Parse HTML → Return to Frontend
```

## Estructura de Datos Retornados

```php
[
    'rfc' => 'MOBL961101GF3',
    'curp' => 'MOBL961101HSLNRS03',
    'razon_social' => 'LUIS GERONIMO MONTES BARRERAS',
    'nombre' => 'LUIS GERONIMO',
    'apellido_paterno' => 'MONTES',
    'apellido_materno' => 'BARRERAS',
    'regimen_fiscal_nombre' => 'Régimen de Sueldos y Salarios e Ingresos Asimilados a Salarios',
    'regimen_fiscal_clave' => '605',
    'direccion_fiscal' => [
        'calle' => 'CEIBITA',
        'numero_exterior' => 'S/N',
        'numero_interior' => null,
        'colonia' => 'AGUILA AZTECA',
        'ciudad' => 'AHOME',
        'estado' => 'SINALOA',
        'codigo_postal' => '81307',
        'pais' => 'México',
    ],
]
```

## Solución de Problemas

### Error: "No se pudo extraer el código QR del PDF"

**Solución 1**: El PDF puede tener el QR como imagen. Instalar zbar o imagick.

**Solución 2**: Verificar que el PDF sea una constancia fiscal válida del SAT.

### Error: "Error al obtener datos del SAT"

**Causa**: Puede ser un problema de red, timeout o cambio en la estructura de la página del SAT.

**Solución**: 
1. Verificar conexión a internet
2. Aumentar el timeout en la configuración
3. Revisar si el SAT cambió la estructura de su página

### Error: "Class 'Smalot\PdfParser\Parser' not found"

**Solución**: Ejecutar `composer install` o `composer require smalot/pdfparser`

## Mejoras Futuras

1. **Cache de consultas al SAT**: Guardar resultados temporalmente para evitar consultas repetidas
2. **Procesamiento asíncrono**: Usar colas para procesar PDFs grandes
3. **Validación adicional**: Verificar que los datos extraídos sean consistentes
4. **Soporte para personas morales**: Adaptar el parser para extraer denominación o razón social de empresas
5. **OCR**: Implementar reconocimiento óptico de caracteres para PDFs escaneados

## Testing

Para probar el servicio:

```bash
php artisan tinker

$service = app(\App\Services\ConstanciaFiscalService::class);
$datos = $service->extraerDatosFiscales('/ruta/al/archivo.pdf');
dd($datos);
```

## Mantenimiento

Revisar periódicamente:
- Cambios en la estructura HTML del SAT
- Actualizaciones en los regímenes fiscales
- Logs de errores en `storage/logs/laravel.log`

## Soporte

Para reportar problemas o sugerir mejoras, contactar al equipo de desarrollo.
