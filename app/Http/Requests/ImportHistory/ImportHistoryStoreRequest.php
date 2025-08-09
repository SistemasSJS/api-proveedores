<?php

namespace App\Http\Requests\ImportHistory;

use Illuminate\Foundation\Http\FormRequest;

class ImportHistoryStoreRequest extends FormRequest
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
            'job_id' => 'nullable|string|max:255',
            'tipo' => 'required|string|in:productos,marcas,categorias,mixed',
            'archivo' => 'nullable|string|max:500',
            'formato' => 'required|string|in:csv,xlsx,json',
            'estado' => 'nullable|string|in:pending,processing,completed,failed,cancelled',
            'fase' => 'nullable|string|in:upload,validation,processing,cleanup,completed',
            'logs' => 'nullable|array',
            'eta_seconds' => 'nullable|integer|min:0',
            'mem_peak_mb' => 'nullable|numeric|min:0',
            'total_registros' => 'nullable|integer|min:0',
            'nuevos' => 'nullable|integer|min:0',
            'actualizados' => 'nullable|integer|min:0',
            'eliminados' => 'nullable|integer|min:0',
            'errores' => 'nullable|integer|min:0',
            'preview_data' => 'nullable|array',
            'errores_detalle' => 'nullable|array',
            'progreso' => 'nullable|numeric|min:0|max:100',
            'inicio_proceso' => 'nullable|date',
            'fin_proceso' => 'nullable|date',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'job_id' => 'ID del trabajo',
            'tipo' => 'tipo de importación',
            'archivo' => 'archivo',
            'formato' => 'formato',
            'estado' => 'estado',
            'fase' => 'fase',
            'logs' => 'logs',
            'eta_seconds' => 'tiempo estimado en segundos',
            'mem_peak_mb' => 'pico de memoria en MB',
            'total_registros' => 'total de registros',
            'nuevos' => 'registros nuevos',
            'actualizados' => 'registros actualizados',
            'eliminados' => 'registros eliminados',
            'errores' => 'errores',
            'preview_data' => 'datos de vista previa',
            'errores_detalle' => 'detalle de errores',
            'progreso' => 'progreso',
            'inicio_proceso' => 'inicio del proceso',
            'fin_proceso' => 'fin del proceso',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de importación es obligatorio.',
            'tipo.in' => 'El tipo de importación debe ser uno de: productos, marcas, categorias, mixed.',
            'formato.required' => 'El formato es obligatorio.',
            'formato.in' => 'El formato debe ser uno de: csv, xlsx, json.',
            'estado.in' => 'El estado debe ser uno de: pending, processing, completed, failed, cancelled.',
            'fase.in' => 'La fase debe ser una de: upload, validation, processing, cleanup, completed.',
            'progreso.min' => 'El progreso no puede ser menor a 0%.',
            'progreso.max' => 'El progreso no puede ser mayor a 100%.',
        ];
    }
}
