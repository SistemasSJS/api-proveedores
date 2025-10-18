<?php

namespace App\Rules;

use App\Enums\EstadoOrdenCompra;
use App\Models\OrdenCompra;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OCAprobada implements ValidationRule
{
    protected ?int $proveedorId;

    public function __construct(?int $proveedorId = null)
    {
        $this->proveedorId = $proveedorId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ordenCompraId = (int) $value;
        
        $query = OrdenCompra::where('id', $ordenCompraId);
        
        // Si tenemos proveedor ID, validar que la OC pertenezca al proveedor
        if ($this->proveedorId) {
            $query->where('proveedor_id', $this->proveedorId);
        }
        
        $ordenCompra = $query->first();
        
        if (!$ordenCompra) {
            $fail('La orden de compra especificada no existe o no pertenece al proveedor.');
            return;
        }

        if ($ordenCompra->estado !== EstadoOrdenCompra::APROBADA) {
            $estadoActual = $ordenCompra->estado->label();
            $fail("La orden de compra debe estar aprobada para generar solicitudes de pago. Estado actual: {$estadoActual}.");
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'La orden de compra debe estar en estado aprobada para poder generar solicitudes de pago.';
    }
}
