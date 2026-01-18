<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Usuario final de la plataforma (tabla global sin business_id).
 * 
 * Los usuarios pueden:
 * - Reservar citas en múltiples negocios (como clientes)
 * - Tener roles de staff/admin en negocios específicos vía business_user_roles
 * 
 * @property int $id
 * @property string $nombre
 * @property string|null $apellidos
 * @property string $email
 * @property string|null $telefono
 * @property string $password
 * @property string|null $foto_perfil_url
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'apellidos',
        'email',
        'telefono',
        'password',
        'foto_perfil_url',
        'current_business_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Roles del usuario en diferentes negocios (multi-tenant).
     */
    public function businessRoles()
    {
        return $this->hasMany(BusinessUserRole::class);
    }

    /**
     * Negocios donde el usuario tiene roles asignados.
     */
    public function businesses()
    {
        return $this->belongsToMany(Business::class, 'business_user_roles')
            ->withPivot('role_id', 'asignado_el')
            ->withTimestamps();
    }

    /**
     * Citas del usuario como cliente.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Verificar si el usuario tiene un rol específico en un negocio.
     */
    public function hasRoleInBusiness(string $roleName, int $businessId): bool
    {
        return $this->businessRoles()
            ->whereHas('role', fn($q) => $q->where('nombre', $roleName))
            ->where('business_id', $businessId)
            ->exists();
    }

    /**
     * Verificar si el usuario tiene un permiso específico en un negocio.
     */
    public function hasPermissionInBusiness(string $permissionName, int $businessId): bool
    {
        return $this->businessRoles()
            ->where('business_id', $businessId)
            ->whereHas('role.permissions', fn($q) => $q->where('nombre', $permissionName))
            ->exists();
    }
}
