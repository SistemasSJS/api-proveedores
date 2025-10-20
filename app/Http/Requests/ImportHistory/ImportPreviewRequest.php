<?php

namespace App\Http\Requests\ImportHistory;

use Illuminate\Foundation\Http\FormRequest;

class ImportPreviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:102400', // 100MB
            ],
            'delimiter' => [
                'string',
                'in:,;|',
                'nullable',
            ],
            'encoding' => [
                'string',
                'in:UTF-8,ISO-8859-1,Windows-1252',
                'nullable',
            ],
            'has_header' => [
                'boolean',
                'nullable',
            ],
            'preview_rows' => [
                'integer',
                'min:1',
                'max:1000',
                'nullable',
            ],
            'strict_validation' => [
                'boolean',
                'nullable',
            ],
            'auto_create_relations' => [
                'boolean',
                'nullable',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'csv_file' => 'archivo CSV',
            'delimiter' => 'delimitador',
            'encoding' => 'codificación',
            'has_header' => 'tiene cabeceras',
            'preview_rows' => 'filas de preview',
            'strict_validation' => 'validación estricta',
            'auto_create_relations' => 'crear relaciones automáticamente',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'El archivo CSV es obligatorio.',
            'csv_file.file' => 'Debe ser un archivo válido.',
            'csv_file.mimes' => 'El archivo debe ser de tipo CSV.',
            'csv_file.max' => 'El archivo no puede superar los 100MB.',
            'delimiter.in' => 'El delimitador debe ser: coma (,), punto y coma (;) o pipe (|).',
            'encoding.in' => 'La codificación debe ser UTF-8, ISO-8859-1 o Windows-1252.',
            'preview_rows.min' => 'Debe previsualizar al menos 1 fila.',
            'preview_rows.max' => 'No se pueden previsualizar más de 1000 filas.',
        ];
    }

    /**
     * Get the default values for optional parameters.
     */
    public function getDefaults(): array
    {
        return [
            'delimiter' => ',',
            'encoding' => 'UTF-8',
            'has_header' => true,
            'preview_rows' => 100,
            'strict_validation' => false,
            'auto_create_relations' => false,
        ];
    }

    /**
     * Get validated data with defaults applied.
     */
    public function getValidatedWithDefaults(): array
    {
        $validated = $this->validated();
        $defaults = $this->getDefaults();

        return array_merge($defaults, $validated);
    }
}
