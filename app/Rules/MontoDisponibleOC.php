<?php

namespace App\Rules;

use App\Models\OrdenCompra;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MontoDisponibleOC implements ValidationRule
{
    protected ?int $ordenCompraId;

    protected ?int $proveedorId;

    public function __construct(?int $ordenCompraId = null, ?int $proveedorId = null)
    {
        $this->ordenCompraId = $ordenCompraId;
        $this->proveedorId = $proveedorId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Si no tenemos ID de OC, no podemos validar
        if (! $this->ordenCompraId) {
            return;
        }

        $query = OrdenCompra::where('id', $this->ordenCompraId);

        // Si tenemos proveedor ID, validar que la OC pertenezca al proveedor
        if ($this->proveedorId) {
            $query->where('proveedor_id', $this->proveedorId);
        }

        $ordenCompra = $query->first();

        if (! $ordenCompra) {
            $fail('La orden de compra especificada no existe o no pertenece al proveedor.');

            return;
        }

        $montoDisponible = $ordenCompra->getMontoDisponible();
        $montoSolicitado = (float) $value;

        if ($montoSolicitado > $montoDisponible) {
            $fail("El monto solicitado ({$montoSolicitado}) excede el monto disponible en la orden de compra ({$montoDisponible}).");
        }

        if ($montoSolicitado <= 0) {
            $fail('El monto debe ser mayor a cero.');
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'El monto especificado excede el monto disponible en la orden de compra.';
    }
}
