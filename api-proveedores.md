# CATALOG\_STRATEGY.md

## ✅ Objetivo

Diseñar un servicio en Laravel que permita:

- Almacenar productos de múltiples proveedores en una sola tabla.
- Adaptarse a diferentes estructuras de origen.
- Mantener un esquema flexible usando `metadata`.
- Exportar datos en diversos formatos y estructuras según la configuración de cada proveedor.

---

## ✅ Estructura de tabla de productos

### **Tabla `productos`**

```sql
CREATE TABLE productos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proveedor_id BIGINT UNSIGNED NOT NULL,
  sku VARCHAR(100),
  nombre VARCHAR(255),
  descripcion TEXT,
  precio DECIMAL(10, 2),
  stock INT,
  categoria VARCHAR(150),
  metadata JSON,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

> **metadata**: almacena campos específicos de cada proveedor (ejemplo: color, talla, marca, peso, dimensiones, etc.).

---

## ✅ Importación

Cada importador por proveedor:

- Procesa su fuente (CSV, Excel, API, etc.)
- Mapea las columnas comunes a los campos estándar.
- Los campos adicionales los agrega en `metadata`.

### **Ejemplo de mapeo al importar:**

```php
$producto = new Producto();
$producto->proveedor_id = $proveedor->id;
$producto->sku = $data['codigo_interno'];
$producto->nombre = $data['nombre_producto'];
$producto->precio = $data['precio_unitario'];
$producto->stock = $data['cantidad_disponible'];
$producto->categoria = $data['linea'] ?? 'general';
$producto->metadata = json_encode([
   'color' => $data['color'],
   'marca' => $data['marca'],
   'modelo' => $data['modelo']
]);
$producto->save();
```

---

## ✅ Exportación dinámica

- Se usa **ExporterStrategy** para definir el formato (CSV, Excel, JSON, PDF).
- Se usa **StructureStrategy** para definir qué columnas y campos exportar, incluidos los datos desde `metadata`.

### **Ejemplo de estructura personalizada por proveedor:**

```php
class ProveedorAStructure implements ExportStructureStrategy {
    public function headers(): array {
        return ['SKU', 'Nombre', 'Precio', 'Color', 'Marca'];
    }

    public function map(array $productos): array {
        return collect($productos)->map(function ($item) {
            $metadata = json_decode($item['metadata'], true);
            return [
                $item['sku'],
                $item['nombre'],
                $item['precio'],
                $metadata['color'] ?? '-',
                $metadata['marca'] ?? '-'
            ];
        })->toArray();
    }
}
```

### **Ejemplo en el controlador:**

```php
public function export(Proveedor $proveedor) {
    $productos = $proveedor->productos()->get()->toArray();

    $structure = StructureFactory::make($proveedor->export_structure_strategy);
    $mappedData = $structure->map($productos);
    $headers = $structure->headers();

    $exporter = ExporterFactory::make($proveedor->export_method);
    return $exporter->export($mappedData, $headers);
}
```

---

## ✅ Ventajas de este diseño

- Solo una tabla para todos los productos.
- Máxima flexibilidad: campos variables via `metadata`.
- Estructuras de exportación y formatos completamente configurables.
- Fácil escalabilidad agregando más proveedores sin tocar la base de datos.

---
    
## ✅ Diagrama resumen

```
+----------------------------+
|          proveedores       |
+----------------------------+
| id                         |
| nombre                     |
| export_method              |
| export_structure_strategy  |
+----------------------------+
        | 1
        |        
        | *
+----------------------------+
|         productos          |
+----------------------------+
| id                         |
| proveedor_id               |
| sku                        |
| nombre                     |
| descripcion                |
| precio                     |
| stock                      |
| categoria                  |
| metadata (json)            |
| created_at                 |
| updated_at                 |
+----------------------------+
```

---

## ✅ UML de clases POO

```plaintext
+------------------------+
| ExportStructureStrategy|
+------------------------+
| +headers(): array      |
| +map(array): array     |
+------------------------+
          ^
          |
+-----------------------------+
| ProveedorAStructure         |
+-----------------------------+
| +headers(): array           |
| +map(array): array          |
+-----------------------------+

+------------------------+
| ExporterStrategy       |
+------------------------+
| +export(data, headers) |
+------------------------+
          ^
          |
+-----------------------------+
| CSVExporter                   |
| ExcelExporter                  |
| JsonExporter                   |
+-----------------------------+

+---------------------+
| StructureFactory    |
+---------------------+
| +make(strategyName) |
+---------------------+

+---------------------+
| ExporterFactory     |
+---------------------+
| +make(methodName)   |
+---------------------+

+---------------------+
| Proveedor           |
+---------------------+
| id                  |
| nombre              |
| export_method       |
| export_structure_   |
| strategy            |
+---------------------+
| +productos()        |
+---------------------+
```

---

## ✅ Conclusión

Este enfoque permite mantener un catálogo extendido de proveedores:

- Centralizado.
- Estructuralmente flexible.
- Exportable en cualquier formato y estructura adaptada al proveedor.

