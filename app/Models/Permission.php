<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Permiso granular del sistema RBAC (tabla global sin business_id).
 * 
 * 26 permisos con formato módulo.acción:
 * - perfil.read, perfil.update
 * - negocio.read, negocio.update
 * - sucursal.read, sucursal.create, sucursal.update, sucursal.delete
 * - servicio.read, servicio.create, servicio.update, servicio.delete
 * - empleado.read, empleado.create, empleado.update, empleado.delete
 * - agenda.read, agenda.create
 * - cita.read, cita.create, cita.update, cita.delete
 * - reportes.read
 * 
 * @property int $id
 * @property string $nombre
 * @property string $display_name
 * @property string|null $descripcion
 * @property string $modulo
 * @property string $accion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Permission extends Model
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
        'modulo',
        'accion',
    ];

    /**
     * Roles que tienen este permiso.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Scope para permisos de un módulo específico.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('modulo', $module);
    }

    /**
     * Scope para permisos de una acción específica.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('accion', $action);
    }
}
