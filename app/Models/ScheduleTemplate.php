<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_location_id',
        'dia_semana',
        'hora_apertura',
        'hora_cierre',
        'activo',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Relación: Plantilla de horario pertenece a una Sucursal
     */
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    /**
     * Scope: Solo horarios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Horarios de un día específico
     */
    public function scopeDia($query, int $diaSemana)
    {
        return $query->where('dia_semana', $diaSemana);
    }
}
