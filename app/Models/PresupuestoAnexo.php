<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoAnexo extends BaseModel
{
    use HasFactory;

    protected $table = 'presupuesto_anexos';

    protected static $filters = [
        'presupuesto_id' => 'PresupuestoId',
        'titulo' => 'Titulo',
    ];

    protected $fillable = [
        'presupuesto_id',
        'titulo',
        'descripcion',
        'precio',
        'orden',
        'archivo_path',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'orden' => 'integer',
    ];

    /**
     * @return string[]
     */
    public static function eagerLodable(): array
    {
        return [
            'presupuesto',
        ];
    }

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(Presupuesto::class);
    }

    public function filterByPresupuestoId($query, $value)
    {
        return $query->where('presupuesto_id', $value);
    }

    public function filterByTitulo($query, string $value)
    {
        return $query->where('titulo', 'like', "%{$value}%");
    }

    public function archivoDataUri(): ?string
    {
        $value = trim((string) ($this->archivo_path ?? ''));
        if ($value === '') {
            return null;
        }

        return str_starts_with($value, 'data:image/')
            ? $value
            : null;
    }
}
