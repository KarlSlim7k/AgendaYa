<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleException extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_location_id',
        'fecha',
        'tipo',
        'todo_el_dia',
        'hora_inicio',
        'hora_fin',
        'motivo',
    ];

    protected $casts = [
        'fecha' => 'date',
        'todo_el_dia' => 'boolean',
    ];

    /**
     * Relación: Excepción pertenece a una Sucursal
     */
    public function businessLocation(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    /**
     * Scope: Excepciones de una fecha específica
     */
    public function scopeFecha($query, $fecha)
    {
        return $query->where('fecha', $fecha);
    }

    /**
     * Scope: Excepciones en un rango de fechas
     */
    public function scopeEnRango($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope: Solo excepciones de todo el día
     */
    public function scopeTodoElDia($query)
    {
        return $query->where('todo_el_dia', true);
    }
}
