<?php

namespace App\Http\Requests\ImportHistory;

use Illuminate\Foundation\Http\FormRequest;

class ImportConfirmRequest extends FormRequest
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
            'preview_token' => [
                'required',
                'string',
                'size:64', // Token de 64 caracteres
            ],
            'import_mode' => [
                'string',
                'in:create_only,update_only,upsert',
                'nullable',
            ],
            'chunk_size' => [
                'integer',
                'min:10',
                'max:1000',
                'nullable',
            ],
            'process_async' => [
                'boolean',
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
            'stop_on_error' => [
                'boolean',
                'nullable',
            ],
            'selected_rows' => [
                'array',
                'nullable',
            ],
            'selected_rows.*' => [
                'integer',
                'min:1',
            ],
            'exclude_rows' => [
                'array',
                'nullable',
            ],
            'exclude_rows.*' => [
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'preview_token' => 'token de previsualización',
            'import_mode' => 'modo de importación',
            'chunk_size' => 'tamaño de lote',
            'process_async' => 'procesar asincrónicamente',
            'strict_validation' => 'validación estricta',
            'auto_create_relations' => 'crear relaciones automáticamente',
            'stop_on_error' => 'detener en error',
            'selected_rows' => 'filas seleccionadas',
            'exclude_rows' => 'filas excluidas',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'preview_token.required' => 'El token de previsualización es obligatorio.',
            'preview_token.size' => 'El token de previsualización debe tener 64 caracteres.',
            'import_mode.in' => 'El modo de importación debe ser: create_only, update_only o upsert.',
            'chunk_size.min' => 'El tamaño de lote debe ser al menos 10.',
            'chunk_size.max' => 'El tamaño de lote no puede superar 1000.',
            'selected_rows.*.integer' => 'Las filas seleccionadas deben ser números enteros.',
            'exclude_rows.*.integer' => 'Las filas excluidas deben ser números enteros.',
        ];
    }

    /**
     * Get the default values for optional parameters.
     */
    public function getDefaults(): array
    {
        return [
            'import_mode' => 'upsert',
            'chunk_size' => 100,
            'process_async' => true,
            'strict_validation' => false,
            'auto_create_relations' => false,
            'stop_on_error' => false,
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

    /**
     * Get the import configuration from validated data.
     */
    public function getImportConfiguration(): array
    {
        $data = $this->getValidatedWithDefaults();

        return [
            'import_mode' => $data['import_mode'],
            'chunk_size' => $data['chunk_size'],
            'process_async' => $data['process_async'],
            'strict_validation' => $data['strict_validation'],
            'auto_create_relations' => $data['auto_create_relations'],
            'stop_on_error' => $data['stop_on_error'],
            'selected_rows' => $data['selected_rows'] ?? null,
            'exclude_rows' => $data['exclude_rows'] ?? null,
        ];
    }
}
