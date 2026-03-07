# Buenas Prácticas: Imágenes en PDFs con DomPDF

## ✅ Mejores Prácticas

### 1. Rutas de Imágenes

**❌ Evitar:**
```blade
<img src="http://example.com/logo.png" />
<img src="/storage/logos/logo.png" />
<img src="../assets/logo.png" />
```

**✅ Usar:**
```blade
{{-- Base64 (recomendado para DomPDF) --}}
<img src="data:image/png;base64,{{ $logoBase64 }}" />

{{-- O rutas absolutas del sistema de archivos (solo si GD está disponible) --}}
<img src="{{ public_path('assets/logos/logo.png') }}" />
```

### 2. Formato de Imágenes

**Orden de preferencia:**
1. **JPEG** - Funciona sin GD, mejor para fotos
2. **PNG** - Requiere GD, mejor para logos con transparencia
3. **GIF** - Requiere GD, menos común
4. **SVG** - Soporte limitado en DomPDF

### 3. Conversión a Base64

**✅ Hacer la conversión en el controlador:**
```php
private function convertirLogoABase64(string $ruta): string
{
    if (!file_exists($ruta) || !is_readable($ruta)) {
        return '';
    }
    
    $imageData = file_get_contents($ruta);
    if ($imageData === false) {
        return '';
    }
    
    $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
    $mimeType = match($extension) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        default => 'image/png',
    };
    
    return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
}
```

**❌ Evitar hacer la conversión en la vista Blade:**
```blade
{{-- NO HACER ESTO --}}
@php
    $logo = base64_encode(file_get_contents('logo.png'));
@endphp
```

### 4. Manejo de Errores

**✅ Implementar fallbacks:**
```blade
@if($logoBase64)
    <img src="{{ $logoBase64 }}" alt="Logo" />
@else
    <div class="logo-fallback">{{ $inicial }}</div>
@endif
```

### 5. Configuración de DomPDF

**✅ Configuración recomendada:**
```php
$pdf = Pdf::loadView('pdf.template', $data)
    ->setPaper('a4', 'portrait')
    ->setOption('isRemoteEnabled', false)  // Deshabilitar URLs remotas
    ->setOption('isHtml5ParserEnabled', true)
    ->setOption('defaultFont', 'DejaVu Sans')
    ->setOption('enable-local-file-access', false)  // Si usas base64
    ->setOption('chroot', public_path());
```

### 6. Verificación de GD

**✅ Verificar antes de procesar imágenes:**
```php
if (!extension_loaded('gd')) {
    // Usar fallback de texto o solo JPEG
    return $this->usarFallbackSinImagenes();
}
```

### 7. Optimización de Tamaño

**✅ Optimizar imágenes antes de convertir a base64:**
- Redimensionar imágenes grandes
- Comprimir PNG con herramientas como TinyPNG
- Usar JPEG para fotos (menor tamaño)
- Limitar el número de imágenes en el PDF

### 8. Permisos de Archivos

**✅ Verificar permisos:**
```php
if (!file_exists($ruta) || !is_readable($ruta)) {
    // Manejar error
    return '';
}
```

## ⚠️ Problemas Comunes y Soluciones

### Problema: Imágenes no se muestran

**Causas posibles:**
1. GD no está instalado
2. Ruta incorrecta
3. Permisos insuficientes
4. Formato no soportado

**Solución:**
```php
// Verificar GD
if (!extension_loaded('gd')) {
    // Usar fallback
}

// Verificar ruta
if (!file_exists($ruta)) {
    // Log error y usar fallback
}

// Verificar formato
$extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
    // Formato no soportado
}
```

### Problema: PDF muy pesado

**Soluciones:**
1. Reducir resolución de imágenes
2. Usar JPEG en lugar de PNG cuando sea posible
3. Comprimir imágenes antes de convertir a base64
4. Limitar tamaño máximo de imágenes

### Problema: Timeout al generar PDF

**Soluciones:**
1. Deshabilitar carga remota: `isRemoteEnabled => false`
2. Usar base64 en lugar de URLs
3. Optimizar imágenes antes de incluirlas
4. Aumentar `max_execution_time` si es necesario

## 📚 Referencias

- [DomPDF Requirements](https://github.com/dompdf/dompdf/wiki/Requirements)
- [Barryvdh Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf)
- [PHP GD Documentation](https://www.php.net/manual/en/book.image.php)
