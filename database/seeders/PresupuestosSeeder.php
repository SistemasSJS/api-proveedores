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

/**
 * Seeder general de presupuestos para todos los proveedores.
 * Genera datos congruentes con las validaciones del frontend y la especificación de condiciones.
 */
class PresupuestosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $proveedores = $this->obtenerProveedoresConAcceso();

            if ($proveedores->isEmpty()) {
                throw new RuntimeException('No hay proveedores con usuarios asignados. Ejecuta ProveedorSeeder primero.');
            }

            $plantillas = $this->obtenerPlantillasPresupuestos();

            foreach ($proveedores as $proveedor) {
                $userId = $this->resolverUserIdParaProveedor($proveedor);
                $siguienteConsecutivo = $this->resolverConsecutivoInicial($proveedor);
                $clientes = $this->crearClientesParaProveedor($proveedor->id);

                foreach ($plantillas as $index => $plantilla) {
                    $folio = $this->formatearFolio($siguienteConsecutivo + $index);

                    $presupuesto = Presupuesto::create(array_merge(
                        $this->buildDatosPresupuesto($plantilla, $proveedor, $clientes, $userId, $folio),
                        ['estado' => $plantilla['estado']]
                    ));

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

                    if (in_array($plantilla['estado'], ['enviado', 'aceptado', 'rechazado', 'vencido'])) {
                        $presupuesto->asegurarTokenPublico();
                    }

                    if (isset($plantilla['fecha_vencimiento'])) {
                        $presupuesto->fecha_vencimiento = $plantilla['fecha_vencimiento'];
                    }

                    $presupuesto->save();
                }

                $proveedor->consecutivo_presupuesto_siguiente = $siguienteConsecutivo + count($plantillas);
                $proveedor->save();
            }

            $this->command->info('Presupuestos creados: ' . (count($proveedores) * count($plantillas)));
        });
    }

    private function obtenerProveedoresConAcceso()
    {
        return Proveedor::withoutGlobalScopes()
            ->whereIn('id', UserProveedor::where('activo', true)->pluck('proveedor_id'))
            ->orderBy('id')
            ->limit(5)
            ->get();
    }

    /**
     * @return array<string, CarteraCliente>
     */
    private function crearClientesParaProveedor(int $proveedorId): array
    {
        $clientes = [];

        $clientes['alfa'] = CarteraCliente::firstOrCreate(
            ['proveedor_id' => $proveedorId, 'nombre' => 'Laura Méndez', 'empresa' => 'Constructora Alfa Norte S.A.'],
            ['puesto' => 'Directora de Compras', 'telefono' => '6681112201', 'correo' => 'laura.mendez@alfanorte.mx']
        );

        $clientes['beta'] = CarteraCliente::firstOrCreate(
            ['proveedor_id' => $proveedorId, 'nombre' => 'Jorge Salinas', 'empresa' => 'Desarrollos Beta Pacífico'],
            ['puesto' => 'Director de Proyecto', 'telefono' => '6681112202', 'correo' => 'jorge.salinas@betapacifico.mx']
        );

        $clientes['gamma'] = CarteraCliente::firstOrCreate(
            ['proveedor_id' => $proveedorId, 'nombre' => 'Martha Ríos', 'empresa' => 'Inmobiliaria Gamma'],
            ['puesto' => 'Gerente Administrativa', 'telefono' => '6681112203', 'correo' => 'martha.rios@gammainmobiliaria.mx']
        );

        return $clientes;
    }

    /**
     * Condiciones según especificación del módulo (vigencia_activo, moneda_activo, etc.)
     */
    private function condicionesBase(): array
    {
        return [
            'vigencia_activo' => true,
            'vigencia_dias' => 15,
            'moneda_activo' => true,
            'impuestos_activo' => true,
            'anticipo_activo' => true,
            'anticipo_porcentaje' => 50,
            'entrega_activo' => true,
            'entrega_tipo' => 'antes',
            'tiempo_entrega_activo' => true,
            'tiempo_entrega_dias' => 10,
            'disponibilidad_materiales_activo' => false,
            'trabajos_adicionales_activo' => true,
            'alcance_activo' => false,
            'cancelacion_activo' => false,
            'autorizacion_gestionpro_activo' => true,
            'condicionantes_adicionales_1' => null,
            'condicionantes_adicionales_2' => null,
            'condicionantes_adicionales_3' => null,
            'condicionantes_adicionales_4' => null,
            'garantia_activo' => true,
            'garantia_dias' => 60,
            'gastos_traslado_activo' => true,
            'gastos_traslado' => 'incluidos',
            'viaticos_activo' => true,
            'viaticos' => 'incluidos',
            'revision_tecnica_activo' => false,
            'condiciones_sitio_activo' => false,
            'observaciones_adicionales_1' => null,
            'observaciones_adicionales_2' => null,
            'observaciones_adicionales_3' => null,
            'observaciones_adicionales_4' => null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function obtenerPlantillasPresupuestos(): array
    {
        $hoy = now();
        $hace15Dias = $hoy->copy()->subDays(15);
        $en5Dias = $hoy->copy()->addDays(5);

        return [
            [
                'estado' => Presupuesto::ESTADO_BORRADOR,
                'fecha_emision' => $hoy->toDateString(),
                'concepto_general' => 'Suministro e instalación de luminarias LED para área de oficinas.',
                'con_iva' => true,
                'iva_porcentaje' => 16.00,
                'observaciones' => 'Incluye garantía de 12 meses por defecto de fabricación.',
                'cliente_key' => 'alfa',
                'empresa_receptora_nombre' => null,
                'empresa_receptora_puesto' => null,
                'empresa_receptora_empresa' => null,
                'empresa_receptora_telefono' => null,
                'empresa_receptora_correo' => null,
                'conceptos' => [
                    ['descripcion' => 'Luminaria panel LED 60x60 cm', 'cantidad' => 20, 'unidad' => 'pieza', 'precio_unitario' => 875.00],
                    ['descripcion' => 'Instalación y puesta en marcha', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 4200.00],
                ],
            ],
            [
                'estado' => Presupuesto::ESTADO_ENVIADO,
                'fecha_emision' => $hoy->copy()->subDays(3)->toDateString(),
                'fecha_vencimiento' => $en5Dias,
                'concepto_general' => 'Mantenimiento preventivo a equipos de aire acondicionado.',
                'con_iva' => false,
                'iva_porcentaje' => 16.00,
                'observaciones' => 'Se agenda visita técnica previa sin costo.',
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
                'estado' => Presupuesto::ESTADO_ACEPTADO,
                'fecha_emision' => $hoy->copy()->subDays(10)->toDateString(),
                'fecha_vencimiento' => $hoy->copy()->addDays(5),
                'concepto_general' => 'Adecuación de red de voz y datos en área administrativa.',
                'con_iva' => true,
                'iva_porcentaje' => 16.00,
                'observaciones' => 'No incluye obra civil ni canalización adicional.',
                'cliente_key' => 'gamma',
                'empresa_receptora_nombre' => null,
                'empresa_receptora_puesto' => null,
                'empresa_receptora_empresa' => null,
                'empresa_receptora_telefono' => null,
                'empresa_receptora_correo' => null,
                'conceptos' => [
                    ['descripcion' => 'Cableado estructurado categoría 6', 'cantidad' => 140, 'unidad' => 'metro', 'precio_unitario' => 58.00],
                    ['descripcion' => 'Faceplates y jacks RJ45', 'cantidad' => 24, 'unidad' => 'pieza', 'precio_unitario' => 95.00],
                    ['descripcion' => 'Configuración de patch panel', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 3200.00],
                ],
            ],
            [
                'estado' => Presupuesto::ESTADO_RECHAZADO,
                'fecha_emision' => $hoy->copy()->subDays(7)->toDateString(),
                'fecha_vencimiento' => $hoy->copy()->subDays(2),
                'concepto_general' => 'Cotización para diseño de landing page y configuración de campañas digitales.',
                'con_iva' => true,
                'iva_porcentaje' => 16.00,
                'observaciones' => 'Proyecto digital estimado en 3 semanas.',
                'cliente_key' => null,
                'empresa_receptora_nombre' => 'Alejandro Torres',
                'empresa_receptora_puesto' => 'Director de Marketing',
                'empresa_receptora_empresa' => 'Grupo Comercial Delta',
                'empresa_receptora_telefono' => '6681112204',
                'empresa_receptora_correo' => 'alejandro.torres@grupodelta.mx',
                'conceptos' => [
                    ['descripcion' => 'Diseño UI/UX de landing', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 9600.00],
                    ['descripcion' => 'Implementación frontend', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 7800.00],
                    ['descripcion' => 'Configuración inicial de campañas', 'cantidad' => 1, 'unidad' => 'servicio', 'precio_unitario' => 4500.00],
                ],
            ],
            [
                'estado' => Presupuesto::ESTADO_VENCIDO,
                'fecha_emision' => $hace15Dias->toDateString(),
                'fecha_vencimiento' => $hace15Dias->copy()->addDays(7),
                'concepto_general' => 'Suministro de insumos de oficina para el trimestre Q1.',
                'con_iva' => true,
                'iva_porcentaje' => 16.00,
                'observaciones' => null,
                'cliente_key' => 'alfa',
                'empresa_receptora_nombre' => null,
                'empresa_receptora_puesto' => null,
                'empresa_receptora_empresa' => null,
                'empresa_receptora_telefono' => null,
                'empresa_receptora_correo' => null,
                'conceptos' => [
                    ['descripcion' => 'Resmas tamaño carta', 'cantidad' => 120, 'unidad' => 'pieza', 'precio_unitario' => 82.00],
                    ['descripcion' => 'Toner láser compatible', 'cantidad' => 14, 'unidad' => 'pieza', 'precio_unitario' => 510.00],
                    ['descripcion' => 'Carpetas y papelería', 'cantidad' => 1, 'unidad' => 'lote', 'precio_unitario' => 2400.00],
                ],
            ],
            [
                'estado' => Presupuesto::ESTADO_BORRADOR,
                'fecha_emision' => $hoy->toDateString(),
                'concepto_general' => 'Reparación de equipo de cómputo y actualización de software.',
                'con_iva' => true,
                'iva_porcentaje' => 16.00,
                'observaciones' => 'Diagnóstico previo sin costo.',
                'cliente_key' => null,
                'empresa_receptora_nombre' => 'Roberto Sánchez',
                'empresa_receptora_puesto' => 'Sistemas',
                'empresa_receptora_empresa' => 'Tech Solutions México',
                'empresa_receptora_telefono' => '5551234567',
                'empresa_receptora_correo' => 'roberto.sanchez@techsolutions.mx',
                'conceptos' => [
                    ['descripcion' => 'Mantenimiento correctivo por equipo', 'cantidad' => 5, 'unidad' => 'pieza', 'precio_unitario' => 450.00],
                    ['descripcion' => 'Licencias de software antivirus', 'cantidad' => 5, 'unidad' => 'pieza', 'precio_unitario' => 320.00],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, CarteraCliente>  $clientes
     * @return array<string, mixed>
     */
    private function buildDatosPresupuesto(array $plantilla, Proveedor $proveedor, array $clientes, int $userId, string $folio): array
    {
        $cliente = $plantilla['cliente_key'] ? ($clientes[$plantilla['cliente_key']] ?? null) : null;

        $condiciones = $this->condicionesBase();
        $condiciones['emisor_razon_social'] = $proveedor->razon_social ?? $proveedor->nombre_comercial;
        $condiciones['emisor_rfc'] = $proveedor->rfc;
        $condiciones['emisor_direccion'] = $proveedor->direccion_empresa;
        $condiciones['emisor_telefono'] = $proveedor->telefono;
        $condiciones['emisor_email'] = $proveedor->email;

        return [
            'numero_presupuesto' => $folio,
            'fecha_emision' => $plantilla['fecha_emision'],
            'concepto_general' => $plantilla['concepto_general'],
            'con_iva' => $plantilla['con_iva'],
            'iva_porcentaje' => $plantilla['iva_porcentaje'],
            'condiciones' => $condiciones,
            'observaciones' => $plantilla['observaciones'],
            'proveedor_id' => $proveedor->id,
            'empresa_receptora_id' => $cliente?->id,
            'empresa_receptora_nombre' => $cliente?->nombre ?? $plantilla['empresa_receptora_nombre'],
            'empresa_receptora_puesto' => $cliente?->puesto ?? $plantilla['empresa_receptora_puesto'],
            'empresa_receptora_empresa' => $cliente?->empresa ?? $plantilla['empresa_receptora_empresa'],
            'empresa_receptora_telefono' => $cliente?->telefono ?? $plantilla['empresa_receptora_telefono'],
            'empresa_receptora_correo' => $cliente?->correo ?? $plantilla['empresa_receptora_correo'],
            'user_id' => $userId,
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
            throw new RuntimeException('No existe ningún usuario para asignar el seeder de presupuestos.');
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
