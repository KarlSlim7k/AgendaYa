<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Asignación multi-tenant de roles a usuarios por negocio.
 * 
 * Un usuario puede tener diferentes roles en diferentes negocios:
 * - Juan es STAFF en Negocio A
 * - Juan es ADMIN en Negocio B
 * 
 * @property int $id
 * @property int $user_id
 * @property int $business_id
 * @property int $role_id
 * @property int|null $assigned_by
 * @property \Illuminate\Support\Carbon $asignado_el
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class BusinessUserRole extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'business_id',
        'role_id',
        'assigned_by',
        'asignado_el',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'asignado_el' => 'datetime',
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

        // Auto-asignar fecha de asignación al crear
        static::creating(function ($model) {
            if (!$model->asignado_el) {
                $model->asignado_el = now();
            }
        });
    }

    /**
     * Usuario al que se le asigna el rol.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Negocio en el que se asigna el rol.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Rol asignado.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Usuario que asignó el rol.
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
