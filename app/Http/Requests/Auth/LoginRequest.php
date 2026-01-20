<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // #region agent log
        // Instrumentación para debug: Verificar configuración de BD antes de autenticar
        try {
            $dbConfig = config('database.connections.mysql');
            \Illuminate\Support\Facades\Log::info('Login Debug - Verificando configuración de BD', [
                'sessionId' => 'debug-session',
                'runId' => 'login-attempt',
                'hypothesisId' => 'C',
                'location' => 'LoginRequest::authenticate',
                'message' => 'Configuración de BD antes de autenticar',
                'data' => [
                    'db_host' => $dbConfig['host'] ?? 'not_set',
                    'db_port' => $dbConfig['port'] ?? 'not_set',
                    'db_database' => $dbConfig['database'] ?? 'not_set',
                    'db_username' => $dbConfig['username'] ?? 'not_set',
                    'db_password_set' => !empty($dbConfig['password'] ?? ''),
                    'db_connection' => config('database.default'),
                    'email_attempt' => $this->input('email'),
                ],
                'timestamp' => now()->timestamp * 1000,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Login Debug - Error al verificar BD', [
                'sessionId' => 'debug-session',
                'runId' => 'login-attempt',
                'hypothesisId' => 'C',
                'location' => 'LoginRequest::authenticate',
                'message' => 'Error al verificar configuración de BD',
                'data' => ['error' => $e->getMessage()],
                'timestamp' => now()->timestamp * 1000,
            ]);
        }
        // #endregion

        try {
            if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => trans('auth.failed'),
                ]);
            }

            RateLimiter::clear($this->throttleKey());
        } catch (\PDOException $e) {
            // #region agent log
            // Capturar errores de conexión a BD
            \Illuminate\Support\Facades\Log::error('Login Debug - Error de conexión a BD', [
                'sessionId' => 'debug-session',
                'runId' => 'login-attempt',
                'hypothesisId' => 'D',
                'location' => 'LoginRequest::authenticate',
                'message' => 'PDOException durante autenticación',
                'data' => [
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage(),
                    'sql_state' => $e->errorInfo[0] ?? 'unknown',
                    'driver_code' => $e->errorInfo[1] ?? 'unknown',
                    'driver_message' => $e->errorInfo[2] ?? 'unknown',
                ],
                'timestamp' => now()->timestamp * 1000,
            ]);
            // #endregion
            
            // Re-lanzar la excepción para que Laravel la maneje
            throw $e;
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
