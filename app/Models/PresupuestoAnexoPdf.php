<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoAnexoPdf extends BaseModel
{
    use HasFactory;

    protected $table = 'presupuesto_anexo_pdf';

    protected static $filters = [
        'presupuesto_id' => 'PresupuestoId',
        'titulo' => 'Titulo',
    ];

    protected $fillable = [
        'presupuesto_id',
        'titulo',
        'orden',
        'archivo_path',
        'paginas',
    ];

    protected $casts = [
        'orden' => 'integer',
        'paginas' => 'integer',
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
}
