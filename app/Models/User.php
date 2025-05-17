<?php

namespace App\Models;
// app/Models/User.php

namespace App\Models;

use App\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="User",
 *     required={"name", "email"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Juan Pérez"),
 *     @OA\Property(property="email", type="string", example="juan@ejemplo.com"),
 *     @OA\Property(property="role_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $fillable = ['name', 'email', 'foto_perfil_url', 'password', 'role_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    // Relación con Role (Un solo rol por usuario)
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Permite obtener todos los proveedores relacionados con un usuario.
     * $user->proveedores()
     */
    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'user_proveedor')
            ->withPivot('is_main')
            ->withTimestamps();
    }
    /**
     * Devuelve solo el proveedor principal de este usuario.
     * $user->mainProveedor()
     */
    public function mainProveedor()
    {
        return $this->belongsToMany(Proveedor::class, 'user_proveedor')
            ->wherePivot('is_main', true)
            ->withTimestamps();
    }

    /**
     * Devuelve el proveedor principal o null si no existe ninguno.
     * $user->main_proveedor.
     */
    public function getMainProveedorAttribute()
    {
        return $this->mainProveedor->first();
    }

    // Filtros disponibles para este modelo
    protected static $filters = [
        'nombre' => 'nombre',
        'email' => 'email',
        'role' => 'role',
    ];

    // Filtro específico para 'name'
    public function filterByNombre($query, $value)
    {
        return $query->where('nombre', 'like', "%$value%");
    }

    // Filtro específico para 'email'
    public function filterByEmail($query, $value)
    {
        return $query->where('email', 'like', "%$value%");
    }

    // Filtro específico para 'role'
    public function filterByRole($query, $value)
    {
        return $query->whereHas('role', function ($q) use ($value) {
            $q->where('name', 'like', "%$value%");
        });
    }

    /**
     * Filtra los resultados de acuerdo a los filtros definidos.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, array $filters)
    {
        foreach ($filters as $key => $value) {
            if (isset(static::$filters[$key])) {
                $method = 'filterBy' . ucfirst($filters[$key]);
                if (method_exists($this, $method)) {
                    $query = $this->$method($query, $value);
                }
            }
        }
        return $query;
    }

    /**
     * Obtener los filtros definidos en la clase.
     *
     * @return array
     */
    public static function getFilters(): array
    {
        return array_values(static::$filters);
    }

    /**
     * Define las relaciones permitidas para cargar con with() (eager loading).
     * Esto evita el problema N+1 y mejora el rendimiento de las consultas.
     *
     * @return string[]
     */
    public static function eagerLodable(): array
    {
        return [
            'role',
            'proveedores',
        ];
    }
}
