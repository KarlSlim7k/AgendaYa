<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Negocio/Tenant del sistema.
 * 
 * Cada negocio es un tenant independiente con sus propias:
 * - Sucursales (business_locations)
 * - Servicios (services)
 * - Empleados (employees)
 * - Citas (appointments)
 * 
 * @property int $id
 * @property string $nombre
 * @property string|null $razon_social
 * @property string|null $rfc
 * @property string $telefono
 * @property string $email
 * @property string $categoria
 * @property string|null $descripcion
 * @property string|null $logo_url
 * @property string $estado pending|approved|suspended|inactive
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Business extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'razon_social',
        'rfc',
        'telefono',
        'email',
        'categoria',
        'descripcion',
        'logo_url',
        'estado',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Sucursales del negocio.
     */
    public function locations()
    {
        return $this->hasMany(BusinessLocation::class);
    }

    /**
     * Usuarios con roles en este negocio.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'business_user_roles')
            ->withPivot('role_id', 'asignado_el')
            ->withTimestamps();
    }

    /**
     * Roles asignados en este negocio.
     */
    public function userRoles()
    {
        return $this->hasMany(BusinessUserRole::class);
    }

    /**
     * Scope para negocios activos/aprobados.
     */
    public function scopeActive($query)
    {
        return $query->where('estado', 'approved');
    }

    /**
     * Scope para negocios pendientes de aprobación.
     */
    public function scopePending($query)
    {
        return $query->where('estado', 'pending');
    }
}
