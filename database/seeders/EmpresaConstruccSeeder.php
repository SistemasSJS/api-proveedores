<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmpresaConstruccSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $proveedores = Proveedor::all();

        foreach ($proveedores as $proveedor) {
            $empresas = [
                [
                    'nombre' => 'Empresa ABC',
                    'rfc' => 'ABC123456789',
                    'razon_social' => 'Empresa ABC S.A. de C.V.',
                    'direccion' => 'Av. Revolución 123, Col. Centro',
                    'ciudad' => 'Ciudad de México',
                    'estado' => 'CDMX',
                    'codigo_postal' => '06000',
                    'telefono' => '5555551234',
                    'email' => 'contacto@empresaabc.com',
                    'proveedor_id' => $proveedor->id,
                    'representante_legal' => 'Juan Pérez García',
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'nombre' => 'Corporativo XYZ',
                    'rfc' => 'XYZ987654321',
                    'razon_social' => 'Corporativo XYZ S.A. de C.V.',
                    'direccion' => 'Blvd. Ángel Urraza 1000, Col. Del Valle',
                    'ciudad' => 'Guadalajara',
                    'estado' => 'Jalisco',
                    'codigo_postal' => '44100',
                    'telefono' => '3333334567',
                    'email' => 'info@corporativoxyz.com',
                    'proveedor_id' => $proveedor->id,
                    'representante_legal' => 'María López Martínez',
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'nombre' => 'Grupo Industrial 123',
                    'rfc' => 'GRP456789123',
                    'razon_social' => 'Grupo Industrial 123 S.A. de C.V.',
                    'direccion' => 'Carretera Nacional Km 10, Zona Industrial',
                    'ciudad' => 'Monterrey',
                    'estado' => 'Nuevo León',
                    'codigo_postal' => '64000',
                    'telefono' => '8181118901',
                    'email' => 'contacto@grupoindustrial123.com',
                    'proveedor_id' => $proveedor->id,
                    'representante_legal' => 'Carlos Rodríguez Sánchez',
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'nombre' => 'Constructora del Norte',
                    'rfc' => 'CDN456789012',
                    'razon_social' => 'Constructora del Norte S.A. de C.V.',
                    'direccion' => 'Av. Universidad 456, Col. San Nicolás',
                    'ciudad' => 'Tijuana',
                    'estado' => 'Baja California',
                    'codigo_postal' => '22000',
                    'telefono' => '6646661234',
                    'email' => 'ventas@constructoradelnorte.com',
                    'proveedor_id' => $proveedor->id,
                    'representante_legal' => 'Ana Flores Herrera',
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'nombre' => 'Servicios Integrales SA',
                    'rfc' => 'SIS789012345',
                    'razon_social' => 'Servicios Integrales SA de C.V.',
                    'direccion' => 'Calle Morelos 789, Col. Jardines',
                    'ciudad' => 'Puebla',
                    'estado' => 'Puebla',
                    'codigo_postal' => '72000',
                    'telefono' => '2222227890',
                    'email' => 'admin@serviciosintegrales.com',
                    'proveedor_id' => $proveedor->id,
                    'representante_legal' => 'Roberto Jiménez Cruz',
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];
            
            DB::table('empresa_construcc')->insert($empresas);
        }
    }
}
