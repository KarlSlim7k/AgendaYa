<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'appointment_id',
        'tipo',
        'evento',
        'estado',
        'intentos',
        'ultimo_intento',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'ultimo_intento' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Global scope para filtrar por business_id
     */
    protected static function booted()
    {
        static::addGlobalScope('business', function (Builder $builder) {
            if (auth()->check() && auth()->user()->current_business_id) {
                $builder->where('business_id', auth()->user()->current_business_id);
            }
        });
    }

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con Business
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Relación con Appointment
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
