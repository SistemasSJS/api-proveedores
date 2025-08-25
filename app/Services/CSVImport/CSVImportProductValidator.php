<?php

namespace App\Services\CSVImport;

use App\Models\Producto;
use App\Models\Marca;
use App\Models\Categoria;
use App\Models\UnidadMedida;

class CSVImportProductValidator
{

    private array $optionalFields = [
        'descripcion',
        'subcategoria'
    ];

    private array $requiredFields = [
        'codigo',
        'producto',
        'marca',
        'categoria',
        'unidad_medida'
    ];

    private array $numericFields = [
        'precio',
        'precio_mayoreo',
        'precio_menudeo'
    ];

    private int $proveedorId;
    private array $existingCodigos = [];
    private array $existingMarcas = [];
    private array $existingCategorias = [];
    private array $existingSubcat = [];
    private array $existingUnidadMedidas = [];

    public function __construct(int $proveedorId)
    {
        $this->proveedorId = $proveedorId;
        $this->loadExistingData();
    }

    /**
     * Validate a single row
     *
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

        // Validate codigo unico
        $codigo = trim($row['codigo'] ?? '');
        if ($codigo) {
            if (in_array($codigo, $this->existingCodigos)) {
                $warnings[] = "Codigo '{$codigo}' ya existe y será actualizado";
            }

            // Validate codigo format (allow alphanumeric, hyphens, underscores, dots, slashes, spaces)
            if (!preg_match('/^[a-zA-Z0-9_\-.\/ ]+$/', $codigo)) {
                $errors[] = "Codigo '{$codigo}' contiene caracteres no válidos";
            }

            // Validate codigo length
            if (strlen($codigo) > 100) {
                $errors[] = "Codigo '{$codigo}' excede la longitud máxima de 100 caracteres";
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
        $marcaNombre = trim($row['marca'] ?? '');
        if ($marcaNombre && strlen($marcaNombre) > 255) {
            $errors[] = "Nombre de marca excede 255 caracteres";
        }

        // Validate categoria
        $categoria = trim($row['categoria'] ?? '');
        if ($categoria && strlen($categoria) > 255) {
            $errors[] = "Nombre de categoría excede 255 caracteres";
        }

        // Validate unidad de medida if provided
        $unidadMedida = trim($row['unidad_medida'] ?? '');
        if ($unidadMedida && !in_array($unidadMedida, $this->existingUnidadMedidas)) {
            $warnings[] = "Unidad de medida '{$unidadMedida}' no existe, se creará automáticamente";
        }

        // Validate product name length
        $nombreProducto = trim($row['producto'] ?? '');
        if ($nombreProducto && strlen($nombreProducto) > 255) {
            $errors[] = "Nombre del producto excede 255 caracteres";
        }

        // Validate description length
        $descripcion = trim($row['descripcion'] ?? '');
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
        // Load existing codigos for this provider
        $this->existingCodigos = Producto::where('proveedor_id', $this->proveedorId)
            ->pluck('codigo_interno')
            ->toArray();

        // Load existing marcas
        $this->existingMarcas = Marca::where('proveedor_id', $this->proveedorId)
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
            'codigo' => 'required',
            'producto' => 'required',
            'descripcion' => 'optional',
            'marca' => 'required',
            'categoria' => 'required',
            'subcategoria' => 'optional',
            'unidad_medida' => 'required',
            'precio' => 'optional',
            'precio_mayoreo' => 'optional',
            'precio_menudeo' => 'optional',
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
