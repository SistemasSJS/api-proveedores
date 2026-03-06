<?php

namespace Database\Seeders;

use App\Models\CarteraCliente;
use App\Models\Presupuesto;
use App\Models\PresupuestoConcepto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\UserProveedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PresupuestoProveedor21Seeder extends Seeder
{
    /**
     * Genera presupuestos de prueba para el proveedor 21.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $proveedor = Proveedor::withoutGlobalScopes()->find(21);

            if (! $proveedor) {
                throw new RuntimeException('No existe el proveedor con ID 21.');
            }

            $userId = $this->resolverUserIdParaProveedor($proveedor);
            $siguienteConsecutivo = $this->resolverConsecutivoInicial($proveedor);

            $clientes = $this->crearClientesBase($proveedor->id);

            $plantillas = $this->obtenerPlantillasPresupuestos();
            foreach ($plantillas as $index => $plantilla) {
                $folio = $this->formatearFolio($siguienteConsecutivo + $index);

                $presupuesto = Presupuesto::create([
                    'numero_presupuesto' => $folio,
                    'fecha_emision' => $plantilla['fecha_emision'],
                    'concepto_general' => $plantilla['concepto_general'],
                    'con_iva' => $plantilla['con_iva'],
                    'iva_porcentaje' => $plantilla['iva_porcentaje'],
                    'condiciones' => $plantilla['condiciones'],
                    'observaciones' => $plantilla['observaciones'],
                    'proveedor_id' => $proveedor->id,
                    'empresa_receptora_id' => $plantilla['cliente_key'] ? $clientes[$plantilla['cliente_key']]->id : null,
                    'empresa_receptora_nombre' => $plantilla['cliente_key'] ? $clientes[$plantilla['cliente_key']]->nombre : $plantilla['empresa_receptora_nombre'],
                    'empresa_receptora_puesto' => $plantilla['cliente_key'] ? $clientes[$plantilla['cliente_key']]->puesto : $plantilla['empresa_receptora_puesto'],
                    'empresa_receptora_empresa' => $plantilla['cliente_key'] ? $clientes[$plantilla['cliente_key']]->empresa : $plantilla['empresa_receptora_empresa'],
                    'empresa_receptora_telefono' => $plantilla['cliente_key'] ? $clientes[$plantilla['cliente_key']]->telefono : $plantilla['empresa_receptora_telefono'],
                    'empresa_receptora_correo' => $plantilla['cliente_key'] ? $clientes[$plantilla['cliente_key']]->correo : $plantilla['empresa_receptora_correo'],
                    'user_id' => $userId,
                ]);

                foreach ($plantilla['conceptos'] as $conceptoIndex => $conceptoData) {
                    $concepto = new PresupuestoConcepto([
                        'numero' => $conceptoIndex + 1,
                        'descripcion' => $conceptoData['descripcion'],
                        'cantidad' => $conceptoData['cantidad'],
                        'unidad' => $conceptoData['unidad'],
                        'precio_unitario' => $conceptoData['precio_unitario'],
                    ]);

                    $concepto->calcularImporte();
                    $presupuesto->conceptos()->save($concepto);
                }

                $presupuesto->recalcularDesdeConceptos();
                $presupuesto->save();
            }

            $proveedor->consecutivo_presupuesto_siguiente = $siguienteConsecutivo + count($plantillas);
            $proveedor->save();
        });
    }

    /**
     * @return array<string, CarteraCliente>
     */
    private function crearClientesBase(int $proveedorId): array
    {
        $clientes = [];

        $clientes['alfa'] = CarteraCliente::firstOrCreate(
            ['proveedor_id' => $proveedorId, 'nombre' => 'Laura Mendez', 'empresa' => 'Constructora Alfa Norte'],
            ['puesto' => 'Compras', 'telefono' => '6681112201', 'correo' => 'laura.mendez@alfanorte.mx']
        );

        $clientes['beta'] = CarteraCliente::firstOrCreate(
            ['proveedor_id' => $proveedorId, 'nombre' => 'Jorge Salinas', 'empresa' => 'Desarrollos Beta Pacifico'],
            ['puesto' => 'Director de Proyecto', 'telefono' => '6681112202', 'correo' => 'jorge.salinas@betapacifico.mx']
        );

        $clientes['gamma'] = CarteraCliente::firstOrCreate(
            ['proveedor_id' => $proveedorId, 'nombre' => 'Martha Rios', 'empresa' => 'Inmobiliaria Gamma'],
            ['puesto' => 'Administracion', 'telefono' => '6681112203', 'correo' => 'martha.rios@gammainmobiliaria.mx']
        );

        return $clientes;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function obtenerPlantillasPresupuestos(): array
    {
        return [
            [
                'fecha_emision' => '2026-02-10',
                'concepto_general' => 'Suministro e instalacion de luminarias LED para area de oficinas.',
                'con_iva' => true,
                'iva_porcentaje' => 16.00,
                'condiciones' => ['anticipo' => '50%', 'tiempo_entrega' => '7 dias habiles'],
                'observaciones' => 'Incluye garantia de 12 meses por defecto de fabricacion.',
                'cliente_key' => 'alfa',
                'empresa_receptora_nombre' => null,
                'empresa_receptora_puesto' => null,
                'empresa_receptora_empresa' => null,
                'empresa_receptora_telefono' => null,
                'empresa_receptora_correo' => null,
                'conceptos' => [
                    ['descripcion' => 'Luminaria panel LED 60x60', 'cantidad' => 20, 'unidad' => 'pieza', 'precio_unitario' => 875.00],
                    ['descripcion' => 'Instalacion y puesta en marcha', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 4200.00],
                ],
            ],
            [
                'fecha_emision' => '2026-02-13',
                'concepto_general' => 'Mantenimiento preventivo a equipos de aire acondicionado.',
                'con_iva' => false,
                'iva_porcentaje' => 16.00,
                'condiciones' => ['forma_pago' => 'Contra entrega'],
                'observaciones' => 'Se agenda visita tecnica previa sin costo.',
                'cliente_key' => 'beta',
                'empresa_receptora_nombre' => null,
                'empresa_receptora_puesto' => null,
                'empresa_receptora_empresa' => null,
                'empresa_receptora_telefono' => null,
                'empresa_receptora_correo' => null,
                'conceptos' => [
                    ['descripcion' => 'Servicio preventivo por equipo', 'cantidad' => 8, 'unidad' => 'pieza', 'precio_unitario' => 650.00],
                    ['descripcion' => 'Material de limpieza especializado', 'cantidad' => 1, 'unidad' => 'lote', 'precio_unitario' => 1800.00],
                ],
            ],
            [
                'fecha_emision' => '2026-02-18',
                'concepto_general' => 'Adecuacion de red de voz y datos en area administrativa.',
                'con_iva' => true,
                'iva_porcentaje' => 16.00,
                'condiciones' => ['vigencia' => '15 dias'],
                'observaciones' => 'No incluye obra civil ni canalizacion adicional.',
                'cliente_key' => 'gamma',
                'empresa_receptora_nombre' => null,
                'empresa_receptora_puesto' => null,
                'empresa_receptora_empresa' => null,
                'empresa_receptora_telefono' => null,
                'empresa_receptora_correo' => null,
                'conceptos' => [
                    ['descripcion' => 'Cableado estructurado categoria 6', 'cantidad' => 140, 'unidad' => 'metro', 'precio_unitario' => 58.00],
                    ['descripcion' => 'Faceplates y jacks', 'cantidad' => 24, 'unidad' => 'pieza', 'precio_unitario' => 95.00],
                    ['descripcion' => 'Configuracion de patch panel', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 3200.00],
                ],
            ],
            [
                'fecha_emision' => '2026-02-24',
                'concepto_general' => 'Cotizacion para diseno de landing page y configuracion de campanas.',
                'con_iva' => true,
                'iva_porcentaje' => 16.00,
                'condiciones' => ['anticipo' => '40%', 'entregables' => 'Diseno + implementacion'],
                'observaciones' => 'Proyecto digital de 3 semanas.',
                'cliente_key' => null,
                'empresa_receptora_nombre' => 'Alejandro Torres',
                'empresa_receptora_puesto' => 'Marketing',
                'empresa_receptora_empresa' => 'Grupo Comercial Delta',
                'empresa_receptora_telefono' => '6681112204',
                'empresa_receptora_correo' => 'alejandro.torres@grupodelta.mx',
                'conceptos' => [
                    ['descripcion' => 'Diseno UI/UX de landing', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 9600.00],
                    ['descripcion' => 'Implementacion frontend', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 7800.00],
                    ['descripcion' => 'Configuracion inicial de campanas', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 4500.00],
                ],
            ],
            [
                'fecha_emision' => '2026-03-01',
                'concepto_general' => 'Suministro de insumos de oficina para el trimestre Q1.',
                'con_iva' => true,
                'iva_porcentaje' => 16.00,
                'condiciones' => ['entrega' => 'Parcial semanal'],
                'observaciones' => null,
                'cliente_key' => 'alfa',
                'empresa_receptora_nombre' => null,
                'empresa_receptora_puesto' => null,
                'empresa_receptora_empresa' => null,
                'empresa_receptora_telefono' => null,
                'empresa_receptora_correo' => null,
                'conceptos' => [
                    ['descripcion' => 'Resmas tamano carta', 'cantidad' => 120, 'unidad' => 'pieza', 'precio_unitario' => 82.00],
                    ['descripcion' => 'Toner laser compatible', 'cantidad' => 14, 'unidad' => 'pieza', 'precio_unitario' => 510.00],
                    ['descripcion' => 'Carpetas y papeleria', 'cantidad' => 1, 'unidad' => 'lote', 'precio_unitario' => 2400.00],
                ],
            ],
        ];
    }

    private function resolverUserIdParaProveedor(Proveedor $proveedor): int
    {
        $userId = UserProveedor::query()
            ->where('proveedor_id', $proveedor->id)
            ->where('activo', true)
            ->value('user_id');

        if ($userId) {
            return (int) $userId;
        }

        if ($proveedor->user_id && User::query()->whereKey($proveedor->user_id)->exists()) {
            return (int) $proveedor->user_id;
        }

        $fallbackUserId = User::query()->orderBy('id')->value('id');
        if (! $fallbackUserId) {
            throw new RuntimeException('No existe ningun usuario para asignar el seeder de presupuestos.');
        }

        return (int) $fallbackUserId;
    }

    private function resolverConsecutivoInicial(Proveedor $proveedor): int
    {
        $maxSecuenciaEnPresupuestos = Presupuesto::query()
            ->where('proveedor_id', $proveedor->id)
            ->pluck('numero_presupuesto')
            ->map(function (?string $numero): int {
                if (! $numero) {
                    return 0;
                }

                if (preg_match('/^PRES-(\d+)$/', $numero, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return max(
            1,
            (int) ($proveedor->consecutivo_presupuesto_siguiente ?? 1),
            $maxSecuenciaEnPresupuestos + 1
        );
    }

    private function formatearFolio(int $consecutivo): string
    {
        return 'PRES-' . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }
}
