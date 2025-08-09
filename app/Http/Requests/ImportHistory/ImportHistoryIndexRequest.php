<?php

namespace App\Http\Requests\ImportHistory;

use Illuminate\Foundation\Http\FormRequest;

class ImportHistoryIndexRequest extends FormRequest
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
            'search' => 'nullable|string|max:255',
            'tipo' => 'nullable|string|in:productos,marcas,categorias,mixed',
            'estado' => 'nullable|string|in:pending,processing,completed,failed,cancelled',
            'formato' => 'nullable|string|in:csv,xlsx,json',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort_by' => 'nullable|string|in:created_at,updated_at,tipo,estado,archivo,total_registros',
            'order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'fase' => 'nullable|string|in:upload,validation,processing,cleanup,completed',
            'has_errors' => 'nullable|boolean',
            'min_registros' => 'nullable|integer|min:0',
            'max_registros' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'search' => 'búsqueda',
            'tipo' => 'tipo de importación',
            'estado' => 'estado',
            'formato' => 'formato',
            'date_from' => 'fecha desde',
            'date_to' => 'fecha hasta',
            'sort_by' => 'ordenar por',
            'order' => 'orden',
            'per_page' => 'por página',
            'fase' => 'fase',
            'has_errors' => 'tiene errores',
            'min_registros' => 'mínimo de registros',
            'max_registros' => 'máximo de registros',
        ];
    }

    /**
     * Get filters for query building
     */
    public function getFilters(): array
    {
        return array_filter([
            'search' => $this->input('search'),
            'tipo' => $this->input('tipo'),
            'estado' => $this->input('estado'),
            'formato' => $this->input('formato'),
            'date_from' => $this->input('date_from'),
            'date_to' => $this->input('date_to'),
            'fase' => $this->input('fase'),
            'has_errors' => $this->input('has_errors'),
            'min_registros' => $this->input('min_registros'),
            'max_registros' => $this->input('max_registros'),
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
