<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Configuración global de la plataforma (tabla global sin business_id).
 * 
 * Almacena settings generales como:
 * - Límites de negocios por plan
 * - Precios de suscripciones
 * - Features habilitados
 * - Configuraciones de email/WhatsApp
 * 
 * @property int $id
 * @property string $clave
 * @property string $valor
 * @property string $tipo
 * @property string|null $descripcion
 * @property bool $editable
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PlatformSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'editable',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'editable' => 'boolean',
    ];

    /**
     * Obtener el valor parseado según su tipo.
     */
    public function getParsedValueAttribute()
    {
        return match ($this->tipo) {
            'integer' => (int) $this->valor,
            'boolean' => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->valor, true),
            default => $this->valor,
        };
    }

    /**
     * Scope para settings editables.
     */
    public function scopeEditable($query)
    {
        return $query->where('editable', true);
    }

    /**
     * Obtener un setting por su clave.
     */
    public static function getByClave(string $clave, $default = null)
    {
        $setting = static::where('clave', $clave)->first();
        return $setting ? $setting->parsed_value : $default;
    }
}
