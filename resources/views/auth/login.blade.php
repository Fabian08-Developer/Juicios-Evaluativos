<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema — SENA Juicios Evaluativos</title>
    <meta name="description" content="Plataforma de Juicios Evaluativos SENA">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #39A900;
            --primary-dark: #2d8500;
            --primary-glow: rgba(57,169,0,0.4);
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            overflow: hidden;
            background: #060d14;
        }

        /* ─── PANEL IZQUIERDO: imagen + overlay ─────────────────────────── */
        .panel-left {
            flex: 1.1;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            overflow: hidden;
        }

        .panel-left-bg {
            position: absolute;
            inset: 0;
            background-image: url('/images/login-bg.jpg?v=2');
            background-size: cover;
            background-position: center center;
            transform: scale(1.03);
            animation: bgZoom 25s ease-in-out infinite alternate;
        }

        @keyframes bgZoom {
            from { transform: scale(1.04); }
            to   { transform: scale(1.12); }
        }

        /* Overlay en capas para contraste y profundidad */
        .panel-left-overlay {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(6,13,20,0.85) 0%, rgba(6,13,20,0.45) 25%, transparent 50%),
                linear-gradient(0deg, rgba(6,13,20,0.98) 0%, rgba(6,13,20,0.65) 35%, rgba(6,13,20,0.2) 65%, transparent 100%),
                linear-gradient(90deg, rgba(6,13,20,0.35) 0%, transparent 100%);
        }

        /* Contenido sobre la imagen */
        .panel-left-content {
            position: relative;
            z-index: 2;
            padding: 3rem 3.5rem;
        }

        .sena-logo-left {
            position: absolute;
            top: 2.25rem;
            left: 2.75rem;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            background: rgba(6, 13, 20, 0.78);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 0.65rem 1.35rem 0.65rem 0.75rem;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 12px 30px -4px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.05);
            animation: fadeIn 0.8s ease both;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .sena-logo-left:hover {
            background: rgba(6, 13, 20, 0.9);
            border-color: rgba(57, 169, 0, 0.4);
            box-shadow: 0 16px 36px -4px rgba(0, 0, 0, 0.75), 0 0 20px rgba(57, 169, 0, 0.25);
            transform: translateY(-2px);
        }
        .sena-icon-wrap {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 6px 18px rgba(57, 169, 0, 0.45);
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .sena-icon-wrap i { font-size: 1.35rem; color: #fff; }
        .sena-logo-text {
            font-size: 1.3rem;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: 0.04em;
            line-height: 1.1;
            text-shadow: 0 2px 10px rgba(0,0,0,0.6);
        }
        .sena-logo-sub {
            font-size: 0.76rem;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: 0.02em;
            margin-top: 0.15rem;
            text-shadow: 0 1px 6px rgba(0,0,0,0.6);
        }

        /* Citas rotativas */
        .quote-block {
            margin-bottom: 0.75rem;
        }
        .quote-marks {
            font-size: 4rem;
            line-height: 1;
            color: var(--primary);
            font-family: Georgia, serif;
            opacity: 0.7;
            margin-bottom: -0.5rem;
        }
        .quote-text {
            font-size: 1.45rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.4;
            letter-spacing: -0.01em;
            max-width: 480px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.5);
        }
        .quote-author {
            margin-top: 0.75rem;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.5);
            font-style: italic;
            letter-spacing: 0.03em;
        }

        /* Stats badges en la parte baja izquierda */
        .stats-row {
            display: flex;
            gap: 1.25rem;
            margin-top: 2rem;
        }
        .stat-chip {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            padding: 0.6rem 1rem;
        }
        .stat-chip i { color: var(--primary); font-size: 0.95rem; }
        .stat-chip-label { font-size: 0.72rem; color: rgba(255,255,255,0.45); line-height: 1; display: block; }
        .stat-chip-value { font-size: 0.88rem; font-weight: 700; color: #fff; line-height: 1; display: block; }

        /* Indicadores de diapositiva */
        .slide-dots {
            position: absolute;
            bottom: 2.5rem;
            right: 2.5rem;
            z-index: 3;
            display: flex;
            gap: 6px;
        }
        .slide-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            cursor: pointer;
            transition: all 0.3s;
        }
        .slide-dot.active {
            background: var(--primary);
            width: 22px;
            border-radius: 3px;
        }

        /* ─── PANEL DERECHO: formulario ─────────────────────────────────── */
        .panel-right {
            width: 460px;
            flex-shrink: 0;
            background: #0b1120;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 3rem;
            position: relative;
            overflow-y: auto;
            border-left: 1px solid rgba(255,255,255,0.05);
        }

        /* Sutil línea verde lateral */
        .panel-right::before {
            content: '';
            position: absolute;
            left: 0;
            top: 20%;
            height: 60%;
            width: 2px;
            background: linear-gradient(to bottom, transparent, var(--primary), transparent);
            opacity: 0.4;
        }

        .form-container {
            width: 100%;
            max-width: 360px;
            animation: slideInRight 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Header del form */
        .form-header { margin-bottom: 2.5rem; }
        .form-eyebrow {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.6rem;
        }
        .form-eyebrow::before {
            content: '';
            width: 20px;
            height: 2px;
            background: var(--primary);
            border-radius: 2px;
        }
        .form-title {
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.03em;
            line-height: 1.15;
        }
        .form-subtitle {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
            line-height: 1.5;
        }

        /* Inputs */
        .form-group { margin-bottom: 1.25rem; }
        .form-label {
            display: block;
            font-size: 0.73rem;
            font-weight: 700;
            color: rgba(148,163,184,0.8);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.5rem;
        }
        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute; left: 1rem; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted); font-size: 0.85rem;
            pointer-events: none; transition: color 0.2s;
        }
        .form-input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 14px;
            color: #f1f5f9;
            font-size: 0.93rem;
            font-family: 'Inter', sans-serif;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            transition: all 0.25s;
            outline: none;
        }
        .form-input::placeholder { color: rgba(100,116,139,0.6); }
        .form-input:focus {
            border-color: var(--primary);
            background: rgba(57,169,0,0.06);
            box-shadow: 0 0 0 3px rgba(57,169,0,0.12);
        }
        .input-wrapper:focus-within .input-icon { color: var(--primary); }
        .input-icon-right {
            position: absolute; right: 1rem; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted); font-size: 0.85rem;
            cursor: pointer; transition: color 0.2s; padding: 0.25rem;
        }
        .input-icon-right:hover { color: #fff; }

        /* Error */
        .error-msg {
            display: flex; align-items: center; gap: 0.5rem;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            color: #fca5a5; font-size: 0.82rem;
            margin-bottom: 1.25rem;
            animation: shakeError 0.4s ease;
        }
        @keyframes shakeError {
            0%,100%{transform:translateX(0)} 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)}
        }
        .field-error {
            font-size: 0.72rem; color: #fca5a5;
            margin-top: 0.4rem;
            display: flex; align-items: center; gap: 0.35rem;
        }

        /* Remember */
        .remember-row {
            display: flex; align-items: center; gap: 0.65rem;
            margin-bottom: 2rem;
        }
        .remember-row input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--primary); cursor: pointer;
        }
        .remember-row label {
            font-size: 0.82rem; color: var(--text-muted);
            cursor: pointer; user-select: none;
        }

        /* Botón */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), #2c8000);
            color: #fff;
            font-size: 0.95rem; font-weight: 800;
            font-family: 'Inter', sans-serif;
            border: none; border-radius: 14px;
            padding: 1rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 0.65rem;
            box-shadow: 0 8px 28px -5px var(--primary-glow);
            transition: all 0.25s;
            letter-spacing: 0.01em;
            position: relative;
            overflow: hidden;
        }
        .btn-login::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0; transition: opacity 0.2s;
        }
        .btn-login:hover::before { opacity: 1; }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 40px -5px var(--primary-glow);
        }
        .btn-login:active { transform: translateY(0); }

        /* Divisor */
        .divider {
            display: flex; align-items: center; gap: 0.75rem;
            margin: 1.75rem 0 1.25rem;
        }
        .divider-line { flex: 1; height: 1px; background: rgba(255,255,255,0.06); }
        .divider-text { font-size: 0.7rem; color: rgba(100,116,139,0.5); white-space: nowrap; }

        /* Footer del form */
        .form-footer {
            margin-top: 2.25rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.05);
            text-align: center;
        }
        .form-footer p { font-size: 0.72rem; color: rgba(100,116,139,0.6); line-height: 1.6; }
        .form-footer strong { color: rgba(255,255,255,0.35); }

        /* Responsive: colapsar en pantallas pequeñas */
        @media (max-width: 900px) {
            .panel-left { display: none; }
            .panel-right { width: 100%; }
        }
    </style>
</head>
<body>

    <!-- ── PANEL IZQUIERDO: Imagen + Contenido ── -->
    <div class="panel-left">
        <div class="panel-left-bg"></div>
        <div class="panel-left-overlay"></div>

        <!-- Logo superior izquierdo -->
        <div class="sena-logo-left">
            <div class="sena-icon-wrap">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <div class="sena-logo-text">SENA</div>
                <div class="sena-logo-sub">Servicio Nacional de Aprendizaje</div>
            </div>
        </div>

        <!-- Contenido inferior -->
        <div class="panel-left-content">
            <div class="quote-block" id="quote-block">
                <div class="quote-marks">"</div>
                <p class="quote-text" id="quote-text">La formación técnica de hoy construye los líderes del mañana.</p>
                <p class="quote-author" id="quote-author">— Misión Institucional SENA</p>
            </div>

            <div class="stats-row">
                <div class="stat-chip">
                    <i class="fa-solid fa-users"></i>
                    <div>
                        <span class="stat-chip-label">Aprendices</span>
                        <span class="stat-chip-value">+9 Millones</span>
                    </div>
                </div>
                <div class="stat-chip">
                    <i class="fa-solid fa-map-marker-alt"></i>
                    <div>
                        <span class="stat-chip-label">Centros</span>
                        <span class="stat-chip-value">+115 Regionales</span>
                    </div>
                </div>
                <div class="stat-chip">
                    <i class="fa-solid fa-award"></i>
                    <div>
                        <span class="stat-chip-label">Desde</span>
                        <span class="stat-chip-value">1957</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Puntos de navegación de citas -->
        <div class="slide-dots">
            <div class="slide-dot active" id="dot-0"></div>
            <div class="slide-dot" id="dot-1"></div>
            <div class="slide-dot" id="dot-2"></div>
        </div>
    </div>

    <!-- ── PANEL DERECHO: Formulario ── -->
    <div class="panel-right">
        <div class="form-container">

            <div class="form-header">
                <div class="form-eyebrow">Acceso Seguro</div>
                <h1 class="form-title">Bienvenido<br>de vuelta</h1>
                <p class="form-subtitle">Ingresa tus credenciales para acceder al sistema de gestión académica.</p>
            </div>

            @if($errors->has('email') && str_contains($errors->first('email'), 'coinciden'))
                <div class="error-msg">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    {{ $errors->first('email') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="login-form">
                @csrf

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope input-icon"></i>
                        <input id="email" type="email" name="email" class="form-input"
                               value="{{ old('email') }}"
                               placeholder="correo@sena.edu.co"
                               autocomplete="email" autofocus>
                    </div>
                    @error('email')
                        <div class="field-error"><i class="fa-solid fa-circle-xmark"></i> {{ $message }}</div>
                    @enderror
                </div>

                <!-- Contraseña -->
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input id="password" type="password" name="password" class="form-input"
                               placeholder="••••••••••" autocomplete="current-password">
                        <i class="fa-solid fa-eye input-icon-right" id="toggle-pass"
                           onclick="togglePassword()" title="Mostrar/ocultar contraseña"></i>
                    </div>
                    @error('password')
                        <div class="field-error"><i class="fa-solid fa-circle-xmark"></i> {{ $message }}</div>
                    @enderror
                </div>

                <!-- Recordarme -->
                <div class="remember-row">
                    <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Mantener sesión iniciada</label>
                </div>

                <!-- Botón -->
                <button type="submit" class="btn-login" id="btn-submit">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                    Ingresar al Sistema
                </button>
            </form>

            <!-- Divisor -->
            <div class="divider">
                <div class="divider-line"></div>
                <span class="divider-text">Plataforma de Gestión Académica</span>
                <div class="divider-line"></div>
            </div>

            <!-- Footer -->
            <div class="form-footer">
                <p>
                    <strong>Servicio Nacional de Aprendizaje — SENA</strong><br>
                    Sistema de Juicios Evaluativos v2.0 &nbsp;·&nbsp; {{ date('Y') }}<br>
                    <span style="font-size:0.65rem; opacity:0.5; margin-top:0.3rem; display:block;">
                        Acceso exclusivo para personal autorizado
                    </span>
                </p>
            </div>

        </div>
    </div>

    <script>
        // Toggle contraseña
        function togglePassword() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('toggle-pass');
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }

        // Loading state en submit
        document.getElementById('login-form').addEventListener('submit', function () {
            const btn = document.getElementById('btn-submit');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
            btn.disabled = true;
        });

        // Rotación de citas
        const quotes = [
            { text: "La formación técnica de hoy construye los líderes del mañana.", author: "— Misión Institucional SENA" },
            { text: "El conocimiento es el activo más valioso que un instructor puede entregar a sus aprendices.", author: "— Instructores SENA" },
            { text: "Cada juicio evaluativo es una oportunidad de transformar vidas a través de la educación.", author: "— Sistema de Gestión Académica" },
        ];
        let currentQuote = 0;

        function changeQuote(index) {
            const textEl   = document.getElementById('quote-text');
            const authorEl = document.getElementById('quote-author');
            const dots     = document.querySelectorAll('.slide-dot');

            textEl.style.opacity = '0';
            textEl.style.transform = 'translateY(8px)';

            setTimeout(() => {
                currentQuote = index ?? (currentQuote + 1) % quotes.length;
                textEl.textContent   = quotes[currentQuote].text;
                authorEl.textContent = quotes[currentQuote].author;
                textEl.style.transition = 'all 0.5s ease';
                textEl.style.opacity = '1';
                textEl.style.transform = 'translateY(0)';
                dots.forEach((d, i) => d.classList.toggle('active', i === currentQuote));
            }, 300);
        }

        // Auto-avance cada 5 segundos
        setInterval(() => changeQuote(), 5000);

        // Clic en puntos
        document.querySelectorAll('.slide-dot').forEach((dot, i) => {
            dot.addEventListener('click', () => changeQuote(i));
        });
    </script>

</body>
</html>
