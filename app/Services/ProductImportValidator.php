<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Marca;
use App\Models\Linea;
use App\Models\Categoria;
use App\Models\UnidadMedida;

class ProductImportValidator
{
    private array $requiredFields = [
        // 'sku',
        'nombre_producto',
        // 'nombre_marca'
    ];

    private array $numericFields = [
        'precio_base',
        'precio_de_lista',
        'precio_publico',
        'precio_mayoreo',
        'precio_con_IVA',
        'precio_sin_IVA',
        'precio_promocional',
        'precio_distribuidor',
        'precio_especial'
    ];

    private int $proveedorId;
    private array $existingSkus = [];
    private array $existingMarcas = [];
    private array $existingLineas = [];
    private array $existingCategorias = [];
    private array $existingUnidadMedidas = [];

    public function __construct(int $proveedorId)
    {
        $this->proveedorId = $proveedorId;
        $this->loadExistingData();
    }

    /**
     * Validate a single row
     *
     * @param array $row
     * @param int $rowIndex (1-based)
     * @return array ['errors' => [], 'warnings' => []]
     */
    public function validateRow(array $row, int $rowIndex): array
    {
        $errors = [];
        $warnings = [];

        // Check required fields
        foreach ($this->requiredFields as $field) {
            if (empty(trim($row[$field] ?? ''))) {
                $errors[] = "Campo obligatorio '{$field}' está vacío";
            }
        }

        // Validate SKU uniqueness within file (will be checked later during batch processing)
        $sku = trim($row['sku'] ?? '');
        if ($sku) {
            if (in_array($sku, $this->existingSkus)) {
                $warnings[] = "SKU '{$sku}' ya existe y será actualizado";
            }

            // Validate SKU format (alphanumeric, hyphens, underscores)
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $sku)) {
                $errors[] = "SKU '{$sku}' contiene caracteres no válidos";
            }

            // Validate SKU length
            if (strlen($sku) > 100) {
                $errors[] = "SKU '{$sku}' excede la longitud máxima de 100 caracteres";
            }
        }

        // Validate numeric fields
        foreach ($this->numericFields as $field) {
            $value = trim($row[$field] ?? '');
            if ($value !== '' && !is_numeric($value)) {
                $errors[] = "Campo '{$field}' debe ser numérico";
            }
            if ($value !== '' && (float)$value < 0) {
                $warnings[] = "Campo '{$field}' tiene valor negativo";
            }
        }

        // Validate marca
        $marcaNombre = trim($row['nombre_marca'] ?? '');
        if ($marcaNombre && strlen($marcaNombre) > 255) {
            $errors[] = "Nombre de marca excede 255 caracteres";
        }

        // Validate linea
        $lineaNombre = trim($row['nombre_linea'] ?? '');
        if ($lineaNombre && strlen($lineaNombre) > 255) {
            $errors[] = "Nombre de línea excede 255 caracteres";
        }

        // Validate categorias (3-level nesting)
        $categoria1 = trim($row['nombre_categoria_nivel_1'] ?? '');
        $categoria2 = trim($row['nombre_categoria_nivel_2'] ?? '');
        $categoria3 = trim($row['nombre_categoria_nivel_3'] ?? '');

        if ($categoria2 && !$categoria1) {
            $errors[] = "No se puede definir categoría nivel 2 sin categoría nivel 1";
        }
        if ($categoria3 && !$categoria2) {
            $errors[] = "No se puede definir categoría nivel 3 sin categoría nivel 2";
        }

        // Validate unidad de medida if provided
        $unidadMedida = trim($row['unidad_medida'] ?? '');
        if ($unidadMedida && !in_array($unidadMedida, $this->existingUnidadMedidas)) {
            $warnings[] = "Unidad de medida '{$unidadMedida}' no existe, se creará automáticamente";
        }

        // Validate product name length
        $nombreProducto = trim($row['nombre_producto'] ?? '');
        if ($nombreProducto && strlen($nombreProducto) > 255) {
            $errors[] = "Nombre del producto excede 255 caracteres";
        }

        // Validate description length
        $descripcion = trim($row['descripcion_producto'] ?? '');
        if ($descripcion && strlen($descripcion) > 65535) {
            $warnings[] = "Descripción muy larga, puede ser truncada";
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Load existing data for validation
     */
    private function loadExistingData(): void
    {
        // Load existing SKUs for this provider
        $this->existingSkus = Producto::where('proveedor_id', $this->proveedorId)
            ->pluck('sku')
            ->toArray();

        // Load existing marcas
        $this->existingMarcas = Marca::where('proveedor_id', $this->proveedorId)
            ->pluck('nombre')
            ->toArray();

        // Load existing lineas
        $this->existingLineas = Linea::where('proveedor_id', $this->proveedorId)
            ->pluck('nombre')
            ->toArray();

        // Load existing categorias
        $this->existingCategorias = Categoria::where('proveedor_id', $this->proveedorId)
            ->pluck('nombre')
            ->toArray();

        // Load existing unidad medidas
        $this->existingUnidadMedidas = UnidadMedida::pluck('descripcion')
            ->toArray();
    }

    /**
     * Get validation rules for CSV headers
     */
    public function getExpectedHeaders(): array
    {
        return [
            'sku' => 'required',
            'nombre_modelo' => 'optional',
            'codigo_interno' => 'optional',
            'nombre_producto' => 'required',
            'descripcion_producto' => 'optional',
            'nombre_marca' => 'required',
            'nombre_linea' => 'optional',
            'nombre_categoria_nivel_1' => 'optional',
            'nombre_categoria_nivel_2' => 'optional',
            'nombre_categoria_nivel_3' => 'optional',
            'unidad_medida' => 'optional',
            'precio_base' => 'optional',
            'precio_de_lista' => 'optional',
            'precio_publico' => 'optional',
            'precio_mayoreo' => 'optional',
            'precio_con_IVA' => 'optional',
            'precio_sin_IVA' => 'optional',
            'precio_promocional' => 'optional',
            'precio_distribuidor' => 'optional',
            'precio_especial' => 'optional'
        ];
    }

    /**
     * Validate CSV headers
     */
    public function validateHeaders(array $headers): array
    {
        $errors = [];
        $warnings = [];
        $expected = $this->getExpectedHeaders();

        // Check for required headers
        foreach ($expected as $header => $requirement) {
            if ($requirement === 'required' && !in_array($header, $headers)) {
                $errors[] = "Columna obligatoria '{$header}' no encontrada";
            }
        }

        // Check for unknown headers
        foreach ($headers as $header) {
            if (!array_key_exists($header, $expected)) {
                $warnings[] = "Columna desconocida '{$header}' será ignorada";
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}
