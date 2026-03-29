<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('auth_title', 'AgendaYa')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --bg: #080E1C;
            --bg-2: #0D1526;
            --bg-3: #111D35;
            --teal: #00E5A0;
            --teal-dim: #00C88A;
            --coral: #FF5C38;
            --coral-dim: #E04C2C;
            --white: #F0F4FF;
            --muted: #6B7FA8;
            --border: rgba(255, 255, 255, 0.1);
            --danger: #FF7D7D;
            --font-head: 'Syne', sans-serif;
            --font-body: 'DM Sans', sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--font-body);
            color: var(--white);
            background: radial-gradient(circle at 8% 12%, rgba(0, 229, 160, 0.12), transparent 40%),
                        radial-gradient(circle at 88% 22%, rgba(255, 92, 56, 0.12), transparent 42%),
                        var(--bg);
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.022) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.022) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse at center, black 25%, transparent 85%);
            z-index: -2;
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            opacity: 0.3;
            pointer-events: none;
            z-index: -1;
        }

        .auth-nav {
            width: min(1120px, calc(100% - 32px));
            margin: 20px auto 0;
            height: 70px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: rgba(13, 21, 38, 0.82);
            backdrop-filter: blur(14px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }

        .nav-logo {
            text-decoration: none;
            color: var(--white);
            font-family: var(--font-head);
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .nav-logo span { color: var(--teal); }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .back-link {
            text-decoration: none;
            font-size: 0.9rem;
            color: var(--muted);
            padding: 10px 12px;
            border-radius: 8px;
            transition: color 0.2s ease, background 0.2s ease;
        }

        .back-link:hover {
            color: var(--white);
            background: rgba(255, 255, 255, 0.06);
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: 1px solid var(--border);
            color: var(--white);
            font-weight: 600;
            font-size: 0.88rem;
            padding: 10px 16px;
            border-radius: 9px;
            transition: all 0.2s ease;
        }

        .btn-ghost:hover {
            border-color: rgba(0, 229, 160, 0.55);
            color: var(--teal);
        }

        .auth-main {
            width: min(1120px, calc(100% - 32px));
            margin: 26px auto 40px;
            display: grid;
            grid-template-columns: 1.08fr 0.92fr;
            gap: 22px;
            align-items: stretch;
        }

        .auth-card,
        .auth-panel {
            border-radius: 20px;
            border: 1px solid var(--border);
            background: rgba(13, 21, 38, 0.86);
            backdrop-filter: blur(16px);
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.46);
        }

        .auth-card {
            padding: 34px 32px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            border: 1px solid rgba(0, 229, 160, 0.28);
            background: rgba(0, 229, 160, 0.1);
            color: var(--teal);
            padding: 6px 12px;
            font-size: 0.7rem;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .tag::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--teal);
        }

        .auth-card h1 {
            margin: 16px 0 10px;
            font-family: var(--font-head);
            font-size: clamp(1.95rem, 3vw, 2.55rem);
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .auth-subtitle {
            margin: 0 0 26px;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.6;
            max-width: 560px;
        }

        .status-banner {
            margin: 0 0 18px;
            border: 1px solid rgba(0, 229, 160, 0.3);
            background: rgba(0, 229, 160, 0.1);
            color: var(--teal);
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 0.92rem;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-size: 0.9rem;
            font-weight: 500;
            color: rgba(240, 244, 255, 0.9);
        }

        .field input {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            background: rgba(8, 14, 28, 0.85);
            color: var(--white);
            font-size: 0.95rem;
            padding: 13px 14px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .field input::placeholder {
            color: rgba(107, 127, 168, 0.75);
        }

        .field input:focus {
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(0, 229, 160, 0.16);
        }

        .field-error {
            margin: 0;
            color: var(--danger);
            font-size: 0.82rem;
        }

        .form-meta {
            margin-top: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .checkbox-wrap {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .checkbox-wrap input {
            width: 16px;
            height: 16px;
            accent-color: var(--teal);
        }

        .text-link {
            color: var(--teal);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
        }

        .text-link:hover { text-decoration: underline; }

        .btn-primary {
            margin-top: 8px;
            border: none;
            border-radius: 10px;
            background: var(--coral);
            color: #fff;
            font-family: var(--font-body);
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            padding: 14px 16px;
            transition: transform 0.15s ease, background 0.2s ease;
        }

        .btn-primary:hover {
            background: var(--coral-dim);
            transform: translateY(-1px);
        }

        .foot-note {
            margin: 18px 0 0;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .foot-note a {
            color: var(--teal);
            text-decoration: none;
            font-weight: 600;
        }

        .foot-note a:hover { text-decoration: underline; }

        .auth-panel {
            position: relative;
            overflow: hidden;
            padding: 38px 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-panel::before {
            content: '';
            position: absolute;
            width: 340px;
            height: 340px;
            right: -120px;
            top: -120px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0, 229, 160, 0.2) 0%, transparent 70%);
            pointer-events: none;
        }

        .auth-panel::after {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            left: -110px;
            bottom: -110px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 92, 56, 0.18) 0%, transparent 70%);
            pointer-events: none;
        }

        .auth-panel h2 {
            margin: 0;
            font-family: var(--font-head);
            font-size: clamp(1.45rem, 2vw, 2.05rem);
            line-height: 1.25;
            letter-spacing: -0.02em;
            position: relative;
            z-index: 1;
        }

        .auth-panel p {
            position: relative;
            z-index: 1;
            margin: 14px 0 24px;
            color: var(--muted);
            line-height: 1.7;
            font-size: 0.96rem;
            max-width: 36ch;
        }

        .benefits {
            position: relative;
            z-index: 1;
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 12px;
        }

        .benefits li {
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 11px;
            background: rgba(8, 14, 28, 0.6);
            padding: 11px 12px 11px 34px;
            font-size: 0.9rem;
            color: rgba(240, 244, 255, 0.92);
            position: relative;
        }

        .benefits li::before {
            content: '✓';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--teal);
            font-weight: 700;
        }

        @media (max-width: 980px) {
            .auth-main {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .auth-panel {
                order: -1;
            }
        }

        @media (max-width: 680px) {
            .auth-nav {
                height: auto;
                gap: 10px;
                align-items: flex-start;
                flex-direction: column;
                padding: 14px;
            }

            .nav-actions {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }

            .auth-card,
            .auth-panel {
                padding: 24px 18px;
                border-radius: 16px;
            }

            .auth-main {
                width: calc(100% - 20px);
                margin-top: 16px;
            }
        }
    </style>
</head>
<body>
    <header class="auth-nav">
        <a href="/index.html" class="nav-logo">Agenda<span>Ya</span></a>
        <div class="nav-actions">
            <a href="/index.html" class="back-link">Volver al inicio</a>
            @yield('nav_action')
        </div>
    </header>

    <main class="auth-main">
        <section class="auth-card">
            <span class="tag">@yield('tag', 'Acceso seguro')</span>
            <h1>@yield('title')</h1>
            <p class="auth-subtitle">@yield('subtitle')</p>

            @if (session('status'))
                <div class="status-banner">{{ session('status') }}</div>
            @endif

            @yield('content')
        </section>

        <aside class="auth-panel">
            <h2>@yield('aside_title', 'Más reservas, menos fricción operativa')</h2>
            <p>@yield('aside_text', 'Administra todo desde un solo panel y mantén una experiencia profesional para tu equipo y tus clientes en cada cita.')</p>
            <ul class="benefits">
                @hasSection('aside_points')
                    @yield('aside_points')
                @else
                    <li>Agenda online activa 24/7 en cualquier dispositivo.</li>
                    <li>Recordatorios automáticos para reducir ausencias.</li>
                    <li>Control de equipo, horarios y rendimiento.</li>
                @endif
            </ul>
        </aside>
    </main>
</body>
</html>