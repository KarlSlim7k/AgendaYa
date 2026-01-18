<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'user_id',
        'employee_id',
        'service_id',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'estado',
        'notas_cliente',
        'notas_internas',
        'motivo_cancelacion',
        'custom_data',
        'confirmada_en',
        'completada_en',
        'cancelada_en',
        'cancelada_por_user_id',
    ];

    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_fin' => 'datetime',
        'custom_data' => 'array',
        'confirmada_en' => 'datetime',
        'completada_en' => 'datetime',
        'cancelada_en' => 'datetime',
    ];

    /**
     * Estados válidos de la cita
     */
    const ESTADO_PENDING = 'pending';
    const ESTADO_CONFIRMED = 'confirmed';
    const ESTADO_COMPLETED = 'completed';
    const ESTADO_CANCELLED = 'cancelled';
    const ESTADO_NO_SHOW = 'no_show';

    /**
     * Transiciones de estado válidas
     */
    const TRANSICIONES_VALIDAS = [
        self::ESTADO_PENDING => [self::ESTADO_CONFIRMED, self::ESTADO_CANCELLED],
        self::ESTADO_CONFIRMED => [self::ESTADO_COMPLETED, self::ESTADO_CANCELLED, self::ESTADO_NO_SHOW],
        self::ESTADO_COMPLETED => [],
        self::ESTADO_CANCELLED => [],
        self::ESTADO_NO_SHOW => [],
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
     * Relación: Cita pertenece a un Negocio
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relación: Cita pertenece a un Usuario (cliente final)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Cita pertenece a un Empleado
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relación: Cita pertenece a un Servicio
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Relación: Usuario que canceló la cita (puede ser el cliente o admin)
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelada_por_user_id');
    }

    /**
     * Validar si una transición de estado es válida
     *
     * @param string $nuevoEstado
     * @return bool
     */
    public function puedeTransicionarA(string $nuevoEstado): bool
    {
        $estadoActual = $this->estado;
        
        if (!isset(self::TRANSICIONES_VALIDAS[$estadoActual])) {
            return false;
        }
        
        return in_array($nuevoEstado, self::TRANSICIONES_VALIDAS[$estadoActual]);
    }

    /**
     * Cambiar estado de la cita con validación de transición
     *
     * @param string $nuevoEstado
     * @param array $datosAdicionales
     * @return bool
     * @throws \App\Exceptions\InvalidStateTransitionException
     */
    public function cambiarEstado(string $nuevoEstado, array $datosAdicionales = []): bool
    {
        if (!$this->puedeTransicionarA($nuevoEstado)) {
            throw new \App\Exceptions\InvalidStateTransitionException(
                "Transición inválida de '{$this->estado}' a '{$nuevoEstado}'"
            );
        }

        $this->estado = $nuevoEstado;

        // Actualizar timestamps según el nuevo estado
        switch ($nuevoEstado) {
            case self::ESTADO_CONFIRMED:
                $this->confirmada_en = now();
                break;
            case self::ESTADO_COMPLETED:
                $this->completada_en = now();
                break;
            case self::ESTADO_CANCELLED:
                $this->cancelada_en = now();
                if (isset($datosAdicionales['cancelada_por_user_id'])) {
                    $this->cancelada_por_user_id = $datosAdicionales['cancelada_por_user_id'];
                }
                if (isset($datosAdicionales['motivo_cancelacion'])) {
                    $this->motivo_cancelacion = $datosAdicionales['motivo_cancelacion'];
                }
                break;
        }

        return $this->save();
    }

    /**
     * Scope: Citas activas (no canceladas ni completadas)
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            self::ESTADO_PENDING,
            self::ESTADO_CONFIRMED,
        ]);
    }

    /**
     * Scope: Citas en un rango de fechas
     */
    public function scopeEnRango(Builder $query, $inicio, $fin): Builder
    {
        return $query->whereBetween('fecha_hora_inicio', [$inicio, $fin]);
    }

    /**
     * Scope: Citas de un empleado específico
     */
    public function scopeDeEmpleado(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope: Citas futuras
     */
    public function scopeFuturas(Builder $query): Builder
    {
        return $query->where('fecha_hora_inicio', '>', now());
    }

    /**
     * Scope: Citas pasadas
     */
    public function scopePasadas(Builder $query): Builder
    {
        return $query->where('fecha_hora_inicio', '<', now());
    }
}
