<?php

namespace App\Rules;

use App\Models\OrdenCompra;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OCUnica implements ValidationRule
{
    protected int $proveedorId;

    protected ?int $exceptoId;

    public function __construct(int $proveedorId, ?int $exceptoId = null)
    {
        $this->proveedorId = $proveedorId;
        $this->exceptoId = $exceptoId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $numeroOrden = (string) $value;

        $query = OrdenCompra::where('numero_orden', $numeroOrden)
            ->where('proveedor_id', $this->proveedorId);

        // Si tenemos un ID a exceptuar (para actualizaciones), lo excluimos
        if ($this->exceptoId) {
            $query->where('id', '!=', $this->exceptoId);
        }

        $existe = $query->exists();

        if ($existe) {
            $fail("Ya existe una orden de compra con el número '{$numeroOrden}' para este proveedor.");
        }
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Ya existe una orden de compra con este número para el proveedor especificado.';
    }
}
