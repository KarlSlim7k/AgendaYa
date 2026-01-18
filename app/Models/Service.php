<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'nombre',
        'descripcion',
        'precio',
        'duracion_minutos',
        'buffer_pre_minutos',
        'buffer_post_minutos',
        'requiere_confirmacion',
        'activo',
        'meta',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'duracion_minutos' => 'integer',
        'buffer_pre_minutos' => 'integer',
        'buffer_post_minutos' => 'integer',
        'requiere_confirmacion' => 'boolean',
        'activo' => 'boolean',
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
     * Relación: Servicio pertenece a un Negocio
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relación: Servicio puede ser realizado por muchos Empleados (N:M)
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_services');
    }
}
