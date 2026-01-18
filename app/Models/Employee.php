<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_account_id',
        'nombre',
        'email',
        'telefono',
        'avatar_url',
        'cargo',
        'estado',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Global Scope Multi-Tenant: Filtrar por business_id del usuario autenticado
     */
    protected static function booted(): void
    {
        static::addGlobalScope('business', function (Builder $builder) {
            if (auth()->check() && auth()->user()->current_business_id) {
                $builder->where('business_id', auth()->user()->current_business_id);
            }
        });
    }

    /**
     * Relación: Empleado pertenece a un Negocio
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relación: Empleado puede tener una cuenta de usuario (opcional)
     */
    public function userAccount(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_account_id');
    }

    /**
     * Relación: Empleado puede realizar muchos Servicios (N:M)
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'employee_services');
    }

    /**
     * Alias en español para la relación services
     */
    public function servicios(): BelongsToMany
    {
        return $this->services();
    }

    /**
     * Scope: Solo empleados disponibles
     */
    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('estado', 'disponible');
    }

    /**
     * Scope: Empleados que pueden realizar un servicio específico
     */
    public function scopeConServicio(Builder $query, int $serviceId): Builder
    {
        return $query->whereHas('services', function ($q) use ($serviceId) {
            $q->where('services.id', $serviceId);
        });
    }
}
