# Guía: Instalación de GD para DomPDF

## 📋 Requisito

DomPDF requiere la extensión **GD** de PHP para procesar imágenes PNG y GIF en los PDFs generados.

## ✅ Verificar si GD está instalado

```bash
php -m | findstr -i gd
```

O crear un archivo PHP temporal:

```php
<?php
if (extension_loaded('gd')) {
    echo "GD está instalado\n";
    print_r(gd_info());
} else {
    echo "GD NO está instalado\n";
}
```

## 🔧 Instalación en Windows (XAMPP/WAMP)

### Opción 1: XAMPP

1. Abrir `C:\xampp\php\php.ini`
2. Buscar la línea: `;extension=gd`
3. Descomentar (quitar el `;`): `extension=gd`
4. Guardar el archivo
5. Reiniciar Apache desde el panel de control de XAMPP

### Opción 2: WAMP

1. Clic derecho en el icono de WAMP en la bandeja del sistema
2. Ir a: `PHP` → `PHP extensions` → Marcar `php_gd2`
3. Reiniciar todos los servicios

### Opción 3: PHP Standalone

1. Abrir `php.ini` (ubicación depende de tu instalación)
2. Buscar y descomentar: `extension=gd`
3. Reiniciar el servidor web

## 🐧 Instalación en Linux (Ubuntu/Debian)

```bash
sudo apt-get update
sudo apt-get install php-gd
sudo systemctl restart apache2  # o nginx, según corresponda
```

## 🍎 Instalación en macOS

```bash
brew install php-gd
# O si usas MacPorts:
sudo port install php-gd
```

## ✅ Verificar instalación después de configurar

```bash
php -m | grep -i gd
```

Deberías ver `gd` en la lista.

## 🔍 Solución de problemas

### Error: "extension=gd" no se encuentra en php.ini

**Solución:** La extensión puede estar comentada con `;` o puede tener un nombre diferente:
- `;extension=gd2`
- `;extension=php_gd2.dll` (Windows)

### Error: "Unable to load dynamic library 'gd'"

**Causas posibles:**
1. El archivo DLL no existe en la carpeta de extensiones de PHP
2. La ruta en `php.ini` es incorrecta
3. Versión incorrecta de la extensión (x86 vs x64)

**Solución:**
1. Verificar que `extension_dir` en `php.ini` apunta a la carpeta correcta
2. Descargar la extensión GD compatible con tu versión de PHP

### Error persiste después de instalar GD

1. Verificar que reiniciaste el servidor web
2. Verificar que estás editando el `php.ini` correcto:
   ```bash
   php --ini
   ```
3. Limpiar caché de Laravel:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## 📝 Notas importantes

- **JPEG funciona sin GD:** Si solo usas imágenes JPEG, DomPDF puede funcionar sin GD
- **PNG/GIF requieren GD:** Estos formatos siempre requieren GD
- **Base64 no evita GD:** Aunque las imágenes estén en base64, DomPDF aún necesita GD para procesarlas

## 🎯 Alternativas si no puedes instalar GD

1. **Usar solo imágenes JPEG:** Convertir todos los logos a formato JPEG
2. **Usar fallback de texto:** El código actual ya implementa iconos de texto cuando GD no está disponible
3. **Cambiar a otra librería:** mPDF o TCPDF tienen mejor soporte sin GD
