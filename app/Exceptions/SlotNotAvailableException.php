<?php

namespace App\Exceptions;

use Exception;

class SlotNotAvailableException extends Exception
{
    protected $message = 'El horario seleccionado ya no está disponible';
    protected $code = 'SLOT_NOT_AVAILABLE';

    public function render($request)
    {
        return response()->json([
            'message' => $this->message,
            'errors' => [
                'fecha_hora_inicio' => [$this->message]
            ],
            'code' => $this->code,
        ], 422);
    }
}
