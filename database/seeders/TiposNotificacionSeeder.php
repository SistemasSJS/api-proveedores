<?php

namespace Database\Seeders;

use App\Models\TipoNotificacion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposNotificacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tabla antes de sembrar
        // TipoNotificacion::truncate();

        $tipos = [
            [
                'codigo' => 'COTIZACION_CREADA',
                'nombre' => 'Cotización Creada',
                'orden_importancia' => 1,
                'descripcion' => 'Notificación enviada cuando se crea una nueva cotización',
                'icono' => 'document-text-outline',
                'color' => 'primary',
                'canales' => ['database', 'mail', 'broadcast'],
                'url_base' => '/admin/cotizaciones',
                'estatus' => true,
            ],
            [
                'codigo' => 'SISTEMA_MANTENIMIENTO',
                'nombre' => 'Mantenimiento del Sistema',
                'orden_importancia' => 2,
                'descripcion' => 'Notificaciones relacionadas con mantenimiento y actualizaciones del sistema',
                'icono' => 'construct-outline',
                'color' => 'warning',
                'canales' => ['database', 'broadcast'],
                'url_base' => '/admin/sistema/mantenimiento',
                'estatus' => true,
            ],
            [
                'codigo' => 'USUARIO_NUEVO',
                'nombre' => 'Nuevo Usuario',
                'orden_importancia' => 3,
                'descripcion' => 'Notificación enviada cuando se registra un nuevo usuario en el sistema',
                'icono' => 'person-add-outline',
                'color' => 'success',
                'canales' => ['database', 'mail'],
                'url_base' => '/admin/usuarios',
                'estatus' => true,
            ],
            [
                'codigo' => 'PAGO_PENDIENTE',
                'nombre' => 'Pago Pendiente',
                'orden_importancia' => 4,
                'descripcion' => 'Notificación para recordar pagos pendientes o vencidos',
                'icono' => 'card-outline',
                'color' => 'danger',
                'canales' => ['database', 'mail', 'broadcast'],
                'url_base' => '/admin/pagos',
                'estatus' => true,
            ],
            [
                'codigo' => 'PRODUCTO_ACTUALIZADO',
                'nombre' => 'Producto Actualizado',
                'orden_importancia' => 5,
                'descripcion' => 'Notificación enviada cuando se actualiza información de productos',
                'icono' => 'cube-outline',
                'color' => 'medium',
                'canales' => ['database'],
                'url_base' => '/admin/productos',
                'estatus' => true,
            ],
            [
                'codigo' => 'MENSAJE_GENERAL',
                'nombre' => 'Mensaje General',
                'orden_importancia' => 6,
                'descripcion' => 'Mensajes generales del sistema o administración',
                'icono' => 'chatbubbles-outline',
                'color' => 'tertiary',
                'canales' => ['database', 'broadcast'],
                'url_base' => '/admin/mensajes',
                'estatus' => true,
            ],
            [
                'codigo' => 'SEGURIDAD_ALERTA',
                'nombre' => 'Alerta de Seguridad',
                'orden_importancia' => 7,
                'descripcion' => 'Alertas relacionadas con la seguridad del sistema',
                'icono' => 'shield-outline',
                'color' => 'danger',
                'canales' => ['database', 'mail', 'broadcast'],
                'url_base' => '/admin/seguridad',
                'estatus' => true,
            ],
            [
                'codigo' => 'BACKUP_COMPLETADO',
                'nombre' => 'Backup Completado',
                'orden_importancia' => 8,
                'descripcion' => 'Notificación enviada cuando se completa un backup del sistema',
                'icono' => 'save-outline',
                'color' => 'success',
                'canales' => ['database'],
                'url_base' => '/admin/sistema/backups',
                'estatus' => true,
            ],
            [
                'codigo' => 'ERROR_SISTEMA',
                'nombre' => 'Error de Sistema',
                'orden_importancia' => 9,
                'descripcion' => 'Notificaciones de errores críticos del sistema',
                'icono' => 'warning-outline',
                'color' => 'danger',
                'canales' => ['database', 'mail'],
                'url_base' => '/admin/sistema/errores',
                'estatus' => true,
            ],
            [
                'codigo' => 'RECORDATORIO_TAREA',
                'nombre' => 'Recordatorio de Tarea',
                'orden_importancia' => 10,
                'descripcion' => 'Recordatorios de tareas pendientes o vencidas',
                'icono' => 'alarm-outline',
                'color' => 'warning',
                'canales' => ['database', 'broadcast'],
                'url_base' => '/admin/tareas',
                'estatus' => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoNotificacion::create($tipo);
        }

        $this->command->info('Se han creado ' . count($tipos) . ' tipos de notificación.');
    }
}
