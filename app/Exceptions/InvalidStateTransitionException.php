<?php

namespace App\Exceptions;

use Exception;

class InvalidStateTransitionException extends Exception
{
    protected $message = 'Transición de estado inválida';
    protected $code = 'INVALID_STATE_TRANSITION';

    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? $this->message);
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'estado' => [$this->getMessage()]
            ],
            'code' => $this->code,
        ], 422);
    }
}
