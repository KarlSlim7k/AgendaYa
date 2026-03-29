@extends('layouts.auth-marketing')

@section('auth_title', 'Iniciar sesión | AgendaYa')
@section('tag', 'Bienvenido de vuelta')
@section('title', 'Inicia sesión en AgendaYa')
@section('subtitle', 'Accede a tu panel para gestionar citas, equipo y horarios desde cualquier dispositivo.')

@section('nav_action')
    <a href="{{ route('register') }}" class="btn-ghost">Crear cuenta</a>
@endsection

@section('aside_title', 'Tu operación diaria, siempre en control')
@section('aside_text', 'Con AgendaYa centralizas reservas, disponibilidad y recordatorios para enfocarte en hacer crecer tu negocio.')

@section('aside_points')
    <li>Agenda online 24/7 para clientes nuevos y frecuentes.</li>
    <li>Recordatorios automáticos para bajar ausencias.</li>
    <li>Vista clara de citas, servicios y equipo en tiempo real.</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
        @csrf

        <div class="field">
            <label for="email">Correo electrónico</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="tu@negocio.com"
            >
            @error('email')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Tu contraseña"
            >
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-meta">
            <label for="remember_me" class="checkbox-wrap">
                <input id="remember_me" type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <span>Recordarme en este dispositivo</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-link" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <button type="submit" class="btn-primary">Entrar al panel</button>
    </form>

    <p class="foot-note">
        ¿Aún no tienes cuenta?
        <a href="{{ route('register') }}">Regístrate gratis</a>
    </p>
@endsection
