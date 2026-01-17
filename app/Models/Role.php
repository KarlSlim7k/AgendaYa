<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Rol del sistema RBAC (tabla global sin business_id).
 * 
 * 5 roles jerárquicos:
 * - USUARIO_FINAL (nivel 0)
 * - NEGOCIO_STAFF (nivel 1)
 * - NEGOCIO_MANAGER (nivel 2)
 * - NEGOCIO_ADMIN (nivel 3)
 * - PLATAFORMA_ADMIN (nivel 4)
 * 
 * @property int $id
 * @property string $nombre
 * @property string $display_name
 * @property string|null $descripcion
 * @property int $nivel_jerarquia
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'display_name',
        'descripcion',
        'nivel_jerarquia',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nivel_jerarquia' => 'integer',
    ];

    /**
     * Permisos asociados a este rol.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /**
     * Asignaciones de este rol a usuarios en negocios.
     */
    public function businessUserRoles()
    {
        return $this->hasMany(BusinessUserRole::class);
    }

    /**
     * Verificar si el rol tiene un permiso específico.
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('nombre', $permissionName)->exists();
    }
}
