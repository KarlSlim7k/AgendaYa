<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sucursal/Locación de un negocio (multi-tenant con business_id).
 * 
 * Cada sucursal tiene:
 * - Dirección física
 * - Horarios propios (schedule_templates)
 * - Excepciones de horario (schedule_exceptions)
 * - Empleados asignados
 * 
 * @property int $id
 * @property int $business_id
 * @property string $nombre
 * @property string $direccion
 * @property string $ciudad
 * @property string $estado
 * @property string $codigo_postal
 * @property string|null $telefono
 * @property string $zona_horaria
 * @property float|null $latitud
 * @property float|null $longitud
 * @property bool $activo
 * @property array|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class BusinessLocation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'nombre',
        'direccion',
        'ciudad',
        'estado',
        'codigo_postal',
        'telefono',
        'zona_horaria',
        'latitud',
        'longitud',
        'activo',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activo' => 'boolean',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'meta' => 'array',
    ];

    /**
     * The "booted" method of the model.
     * 
     * Aplica Global Scope para filtrar por business_id del usuario actual.
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
     * Negocio al que pertenece la sucursal.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Plantillas de horario de la sucursal.
     */
    public function scheduleTemplates()
    {
        return $this->hasMany(ScheduleTemplate::class);
    }

    /**
     * Excepciones de horario de la sucursal.
     */
    public function scheduleExceptions()
    {
        return $this->hasMany(ScheduleException::class);
    }

    /**
     * Scope para sucursales activas.
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }
}
