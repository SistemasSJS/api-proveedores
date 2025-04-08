<?php

namespace Tests\Feature;

use App\Models\Proveedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorTest extends TestCase
{
    use RefreshDatabase;

    // Prueba la creación de un proveedor
    public function test_can_create_proveedor()
    {
        // Realiza la solicitud de registro con los datos del proveedor
        $response = $this->postJson('/api/register', [
            'razon_social' => 'Proveedor S.A. de C.V.',
            'nombre_comercial' => 'Proveedor Comercial',
            'email' => 'test@proveedor.com',
            'password' => 'contraseñaSegura123',
        ]);

        // Verifica que el proveedor se haya creado correctamente
        $response->assertStatus(201)
                 ->assertJson([
                     'razon_social' => 'Proveedor S.A. de C.V.',
                     'nombre_comercial' => 'Proveedor Comercial',
                     'email' => 'test@proveedor.com',
                 ]);

        // Verifica que los datos se hayan guardado en la base de datos
        $this->assertDatabaseHas('proveedores', [
            'razon_social' => 'Proveedor S.A. de C.V.',
            'nombre_comercial' => 'Proveedor Comercial',
            'email' => 'test@proveedor.com',
        ]);
    }

    // Prueba la actualización de un proveedor
    public function test_can_update_proveedor()
    {
        // Crea un proveedor de prueba
        $proveedor = Proveedor::create([
            'razon_social' => 'Proveedor Test S.A.',
            'nombre_comercial' => 'Proveedor Test Comercial',
            'email' => 'test@proveedor.com',
            'telefono' => '1234567890',
        ]);

        // Actualiza el proveedor
        $response = $this->putJson('/api/proveedores/'.$proveedor->id, [
            'razon_social' => 'Proveedor Actualizado S.A.',
            'nombre_comercial' => 'Proveedor Actualizado Comercial',
            'email' => 'actualizado@proveedor.com',
        ]);

        // Verifica que la actualización fue exitosa
        $response->assertStatus(200)
                 ->assertJson([
                     'razon_social' => 'Proveedor Actualizado S.A.',
                     'nombre_comercial' => 'Proveedor Actualizado Comercial',
                     'email' => 'actualizado@proveedor.com',
                 ]);

        // Verifica que los datos fueron actualizados en la base de datos
        $this->assertDatabaseHas('proveedores', [
            'razon_social' => 'Proveedor Actualizado S.A.',
            'nombre_comercial' => 'Proveedor Actualizado Comercial',
            'email' => 'actualizado@proveedor.com',
        ]);
    }

    // Prueba la eliminación de un proveedor
    public function test_can_delete_proveedor()
    {
        // Crea un proveedor de prueba
        $proveedor = Proveedor::create([
            'razon_social' => 'Proveedor Test S.A.',
            'nombre_comercial' => 'Proveedor Test Comercial',
            'email' => 'test@proveedor.com',
            'telefono' => '1234567890',
        ]);

        // Elimina el proveedor
        $response = $this->deleteJson('/api/proveedores/'.$proveedor->id);

        // Verifica que la eliminación fue exitosa
        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Proveedor eliminado correctamente',
                 ]);

        // Verifica que el proveedor ya no esté en la base de datos
        $this->assertDatabaseMissing('proveedores', [
            'id' => $proveedor->id,
        ]);
    }
}
