@extends('layouts.auth-marketing')

@section('auth_title', 'Registro de cuenta | AgendaYa')
@section('tag', 'Prueba gratis por 14 días')
@section('title', 'Crea tu cuenta en AgendaYa')
@section('subtitle', 'Configura tu negocio y empieza a recibir reservas en línea sin complicaciones técnicas.')

@section('nav_action')
    <a href="{{ route('login') }}" class="btn-ghost">Ya tengo cuenta</a>
@endsection

@section('aside_title', 'Del caos operativo a una agenda inteligente')
@section('aside_text', 'Registra tu negocio y en pocos minutos tendrás servicios, horarios y recordatorios listos para trabajar.')

@section('aside_points')
    <li>Onboarding rápido para activar tu perfil en minutos.</li>
    <li>Gestión de empleados, servicios y sucursales desde un panel.</li>
    <li>Escalable para crecer sin cambiar de plataforma.</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('register') }}" class="auth-form" novalidate>
        @csrf

        <div class="field">
            <label for="nombre">Nombre</label>
            <input
                id="nombre"
                type="text"
                name="nombre"
                value="{{ old('nombre') }}"
                required
                autofocus
                autocomplete="given-name"
                placeholder="Tu nombre"
            >
            @error('nombre')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="apellidos">Apellidos (opcional)</label>
            <input
                id="apellidos"
                type="text"
                name="apellidos"
                value="{{ old('apellidos') }}"
                autocomplete="family-name"
                placeholder="Tus apellidos"
            >
            @error('apellidos')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="email">Correo electrónico</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
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
                autocomplete="new-password"
                placeholder="Mínimo 8 caracteres"
            >
            @error('password')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirmar contraseña</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Repite tu contraseña"
            >
            @error('password_confirmation')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary">Crear mi cuenta</button>
    </form>

    <p class="foot-note">
        ¿Ya tienes cuenta?
        <a href="{{ route('login') }}">Inicia sesión aquí</a>
    </p>
@endsection
