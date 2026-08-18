<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SENA — @yield('title', 'Gestión de Juicios Evaluativos')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #39A900;
            --primary-glow: rgba(57, 169, 0, 0.4);
            --primary-dark: #2d8500;
            --bg-dark: #0f172a;
            --sidebar-bg: #0b1120;
            --card-bg: rgba(30, 41, 59, 0.5);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #0ea5e9;
            --glass-border: rgba(255, 255, 255, 0.1);
            --danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background-image:
                radial-gradient(at 0% 0%, rgba(57, 169, 0, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(14, 165, 233, 0.1) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* ===================== SIDEBAR ===================== */
        aside {
            width: 270px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--glass-border);
            padding: 2rem 1.25rem;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 100;
            overflow-y: auto;
        }

        .logo {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
            text-shadow: 0 0 20px var(--primary-glow);
        }

        /* Buscador Global */
        .search-wrapper {
            position: relative;
            margin-bottom: 1.75rem;
        }
        .search-wrapper input {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.65rem 1rem 0.65rem 2.5rem;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        .search-wrapper input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(57,169,0,0.05);
            box-shadow: 0 0 0 3px rgba(57,169,0,0.1);
        }
        .search-wrapper .search-icon {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.8rem;
            pointer-events: none;
            transition: color 0.3s;
        }
        .search-wrapper input:focus ~ .search-icon { color: var(--primary); }

        /* Dropdown de resultados */
        #search-results {
            position: absolute;
            top: calc(100% + 8px);
            left: 0; right: 0;
            background: #1e293b;
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            overflow: hidden;
            z-index: 999;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            display: none;
            max-height: 320px;
            overflow-y: auto;
        }
        #search-results.open { display: block; animation: dropDown 0.2s ease; }
        @keyframes dropDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }

        .search-result-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: background 0.15s;
            text-decoration: none;
            color: inherit;
        }
        .search-result-item:hover { background: rgba(57,169,0,0.08); }
        .search-result-item:last-child { border-bottom: none; }
        .search-avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 800;
            color: #fff;
            flex-shrink: 0;
        }
        .search-no-results {
            padding: 1.5rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /* Nav links */
        .nav-group { margin-bottom: 1.5rem; }
        .nav-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
            padding-left: 0.75rem;
            font-weight: 700;
        }
        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            padding: 0.8rem 1rem;
            border-radius: 12px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            font-size: 0.875rem;
            border: 1px solid transparent;
        }
        .nav-link i { font-size: 1rem; transition: transform 0.3s; }
        .nav-link:hover {
            background: rgba(255,255,255,0.04);
            color: var(--text-main);
            transform: translateX(4px);
        }
        .nav-link.active {
            background: linear-gradient(135deg, rgba(57,169,0,0.15), rgba(57,169,0,0.05));
            color: var(--primary);
            border-color: rgba(57,169,0,0.2);
            box-shadow: 0 4px 12px rgba(57,169,0,0.1);
        }
        .nav-link.active i { transform: scale(1.1); }

        /* Badge en nav */
        .nav-badge {
            margin-left: auto;
            background: var(--primary);
            color: #000;
            font-size: 0.6rem;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 20px;
        }

        /* ===================== MAIN ===================== */
        main {
            flex: 1;
            padding: 2.5rem 3rem;
            max-width: 1600px;
            width: 100%;
            overflow-x: hidden;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--glass-border);
        }
        .header h1 {
            font-size: 2.1rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* ===================== CARDS ===================== */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2rem;
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            transition: transform 0.3s, border-color 0.3s;
        }
        .card:hover { border-color: rgba(255,255,255,0.15); }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem 2rem;
        }
        .icon-box {
            width: 60px; height: 60px;
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2);
            flex-shrink: 0;
        }
        .stat-value { font-size: 2rem; font-weight: 800; line-height: 1; margin-bottom: 0.25rem; }
        .stat-label { color: var(--text-muted); font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

        /* ===================== BOTONES ===================== */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            border: 1px solid transparent;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-family: inherit;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(57,169,0,0.3);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(57,169,0,0.4);
        }
        .btn-outline {
            background: rgba(255,255,255,0.05);
            color: white;
            border: 1px solid var(--glass-border);
        }
        .btn-outline:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.25);
        }
        .btn-danger {
            background: rgba(239,68,68,0.1);
            color: #fca5a5;
            border: 1px solid rgba(239,68,68,0.2);
        }
        .btn-danger:hover { background: rgba(239,68,68,0.2); }

        /* ===================== FORMS ===================== */
        .form-control {
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            padding: 0.6rem 1rem;
            color: white;
            font-family: inherit;
            width: 100%;
            transition: all 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(0,0,0,0.3);
            box-shadow: 0 0 0 3px rgba(57,169,0,0.1);
        }

        /* ===================== BADGES ===================== */
        .badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-success { background: rgba(57,169,0,0.1); color: var(--primary); border: 1px solid rgba(57,169,0,0.2); }
        .badge-warning { background: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2); }
        .badge-info    { background: rgba(14,165,233,0.1); color: var(--accent); border: 1px solid rgba(14,165,233,0.2); }
        .badge-danger  { background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }

        /* ===================== TOAST NOTIFICATIONS ===================== */
        #toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: none;
        }
        .toast {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 1rem 1.5rem;
            border-radius: 16px;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            min-width: 320px;
            max-width: 400px;
            pointer-events: all;
            animation: toastIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            border: 1px solid;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .toast.removing { animation: toastOut 0.3s ease forwards; }
        .toast-success { background: rgba(15,23,42,0.95); border-color: rgba(57,169,0,0.3); color: #86efac; }
        .toast-warning { background: rgba(15,23,42,0.95); border-color: rgba(245,158,11,0.3); color: #fde68a; }
        .toast-error   { background: rgba(15,23,42,0.95); border-color: rgba(239,68,68,0.3); color: #fca5a5; }
        .toast-icon { font-size: 1.1rem; flex-shrink: 0; }
        .toast-close {
            margin-left: auto;
            background: none;
            border: none;
            color: inherit;
            opacity: 0.5;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0.25rem;
            transition: opacity 0.2s;
        }
        .toast-close:hover { opacity: 1; }
        @keyframes toastIn  { from { opacity:0; transform:translateX(30px) scale(0.9); } to { opacity:1; transform:translateX(0) scale(1); } }
        @keyframes toastOut { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(30px); } }

        /* ===================== PAGINACIÓN ===================== */
        nav[role="navigation"] {
            margin-top: 2.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid var(--glass-border);
        }
        nav[role="navigation"] > div:first-child { display: flex; gap: 0.5rem; margin-bottom: 0; }
        nav[role="navigation"] a, nav[role="navigation"] span {
            padding: 0.5rem 1rem;
            border-radius: 10px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            color: #fff;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        nav[role="navigation"] a:hover {
            background: rgba(57,169,0,0.1);
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-1px);
        }
        nav[role="navigation"] span[aria-current="page"] {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 0 15px var(--primary-glow);
        }
        nav[role="navigation"] span[aria-disabled="true"] { opacity: 0.3; cursor: not-allowed; background: transparent; }
        nav[role="navigation"] p { display: none !important; }
        nav[role="navigation"] div:first-child { display: none !important; }
        nav[role="navigation"] div:last-child { display: flex !important; justify-content: center !important; width: 100% !important; }
        nav[role="navigation"] svg { width: 14px; height: 14px; }

        /* ===================== RESPONSIVE ===================== */
        @media (max-width: 1024px) {
            aside { width: 70px; padding: 1.5rem 0.5rem; }
            .logo span, .nav-label, .nav-link span, .search-wrapper, .nav-badge { display: none; }
            .nav-link { justify-content: center; padding: 1rem; }
            main { padding: 2rem 1.5rem; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            aside { width: 100%; height: auto; position: relative; padding: 1rem; flex-direction: row; justify-content: space-between; align-items: center; }
            .logo { margin-bottom: 0; }
            nav { display: flex; gap: 8px; }
            .nav-link { margin-bottom: 0; }
        }
        @media print {
            aside, .header, .btn, nav[role="navigation"] { display: none !important; }
            main { padding: 0; max-width: 100%; }
            .card { box-shadow: none; border: 1px solid #e2e8f0; }
        }
    </style>
</head>
<body>

    <!-- ======================== SIDEBAR ======================== -->
    <aside>
        <div class="logo">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>SENA</span>
        </div>

        <!-- 🔍 Buscador Global -->
        <div class="search-wrapper">
            <input type="text" id="global-search" placeholder="Buscar aprendiz..." autocomplete="off">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <div id="search-results"></div>
        </div>

        <div class="nav-group">
            <div class="nav-label">General</div>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Administración</div>
            <a href="{{ route('fichas.index') }}" class="nav-link {{ request()->routeIs('fichas.*') ? 'active' : '' }}">
                <i class="fa-solid fa-folder-tree"></i> <span>Fichas</span>
            </a>
            <a href="{{ route('aprendices.index') }}" class="nav-link {{ request()->routeIs('aprendices.index') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i> <span>Aprendices</span>
            </a>
            <a href="{{ route('aprendices.upload') }}" class="nav-link {{ request()->routeIs('aprendices.upload') ? 'active' : '' }}">
                <i class="fa-solid fa-cloud-arrow-up"></i> <span>Importar Datos</span>
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label" style="color: #fbbf24;">⚡ Acciones & Innovación</div>
            <a href="{{ route('acciones.matriz') }}" class="nav-link {{ request()->routeIs('acciones.matriz') ? 'active' : '' }}">
                <i class="fa-solid fa-table-cells" style="color: var(--primary);"></i> <span>Matriz de Calificación</span>
            </a>
            <a href="{{ route('acciones.diagnostico') }}" class="nav-link {{ request()->routeIs('acciones.diagnostico') ? 'active' : '' }}">
                <i class="fa-solid fa-traffic-light" style="color: #f59e0b;"></i> <span>Semáforo de Deserción</span>
            </a>
            <a href="{{ route('acciones.cuellos-botella') }}" class="nav-link {{ request()->routeIs('acciones.cuellos-botella') ? 'active' : '' }}">
                <i class="fa-solid fa-filter-circle-xmark" style="color: #ef4444;"></i> <span>Cuellos de Botella</span>
            </a>
            <a href="{{ route('remisiones.index') }}" class="nav-link {{ request()->routeIs('remisiones.*') ? 'active' : '' }}">
                <i class="fa-solid fa-bullhorn" style="color: #ef4444;"></i> <span>Remisiones & Alertas</span>
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Reportes</div>
            <a href="{{ route('importaciones.index') }}" class="nav-link {{ request()->routeIs('importaciones.*') ? 'active' : '' }}">
                <i class="fa-solid fa-clock-rotate-left"></i> <span>Historial</span>
            </a>
        </div>

        <!-- ── PERFIL DE USUARIO Y LOGOUT ── -->
        <div style="margin-top: auto; padding: 1rem 0.5rem 0.5rem; border-top: 1px solid rgba(255,255,255,0.06);">
            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-radius: 14px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); margin-bottom: 0.5rem;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 800; color: #fff; flex-shrink: 0;">
                    {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                </div>
                <div style="min-width: 0; overflow: hidden;">
                    <div style="font-size: 0.82rem; font-weight: 700; color: #f1f5f9; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ Auth::user()->name ?? 'Administrador' }}</div>
                    <div style="font-size: 0.68rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ Auth::user()->email ?? '' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="width: 100%; background: rgba(239,68,68,0.08); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); border-radius: 10px; padding: 0.55rem 1rem; font-size: 0.78rem; font-weight: 700; font-family: inherit; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.15)'" onmouseout="this.style.background='rgba(239,68,68,0.08)'">
                    <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                </button>
            </form>
        </div>
    </aside>

    <!-- ======================== MAIN ======================== -->
    <main>
        <div class="header">
            <div>
                <h1>@yield('title', 'Panel de Control')</h1>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="color: var(--text-muted); font-size: 0.85rem;">
                    <i class="fa-solid fa-circle" style="color: var(--primary); font-size: 0.5rem; animation: pulse 2s infinite;"></i>
                    En línea
                </span>
                <span style="color: var(--text-muted);">Bienvenido,</span>
                <strong>{{ Auth::user()->name ?? 'Administrador' }}</strong>
            </div>
        </div>

        @yield('content')
    </main>

    <!-- ======================== TOAST CONTAINER ======================== -->
    <div id="toast-container"></div>

    <!-- ======================== SCRIPTS ======================== -->
    <script>
        // ============ TOAST SYSTEM ============
        function showToast(message, type = 'success', duration = 5000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const icon = type === 'success' ? 'fa-circle-check' : (type === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-exclamation');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fa-solid ${icon} toast-icon"></i>
                <span>${message}</span>
                <button class="toast-close" onclick="removeToast(this.parentElement)">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;
            container.appendChild(toast);

            const timer = setTimeout(() => removeToast(toast), duration);
            toast._timer = timer;
        }

        function removeToast(toast) {
            clearTimeout(toast._timer);
            toast.classList.add('removing');
            toast.addEventListener('animationend', () => toast.remove(), { once: true });
        }

        // Mostrar toasts de sesión Laravel
        @if(session('success'))
            document.addEventListener('DOMContentLoaded', () => showToast(@json(session('success')), 'success'));
        @endif
        @if(session('warning'))
            document.addEventListener('DOMContentLoaded', () => showToast(@json(session('warning')), 'warning', 8000));
        @endif
        @if(session('error'))
            document.addEventListener('DOMContentLoaded', () => showToast(@json(session('error')), 'error', 8000));
        @endif
        @if($errors->any())
            document.addEventListener('DOMContentLoaded', () => {
                @foreach($errors->all() as $err)
                    showToast(@json($err), 'error', 9000);
                @endforeach
            });
        @endif

        // ============ BUSCADOR GLOBAL ============
        const searchInput   = document.getElementById('global-search');
        const searchResults = document.getElementById('search-results');
        let searchTimer;

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            const q = this.value.trim();

            if (q.length < 2) {
                searchResults.classList.remove('open');
                searchResults.innerHTML = '';
                return;
            }

            searchTimer = setTimeout(async () => {
                try {
                    const res  = await fetch(`/aprendices/buscar?q=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    renderSearchResults(data, q);
                } catch (e) {
                    console.error('Error en búsqueda:', e);
                }
            }, 280);
        });

        function renderSearchResults(items, q) {
            if (!items.length) {
                searchResults.innerHTML = `<div class="search-no-results"><i class="fa-solid fa-user-slash" style="display:block;font-size:1.5rem;margin-bottom:0.5rem;opacity:0.3;"></i>Sin resultados para "<strong>${q}</strong>"</div>`;
                searchResults.classList.add('open');
                return;
            }

            const estadoColors = {
                'EN FORMACION': '#39A900',
                'RETIRO VOLUNTARIO': '#f59e0b',
                'TRASLADADO': '#94a3b8'
            };

            searchResults.innerHTML = items.map(item => `
                <a href="${item.url}" class="search-result-item">
                    <div class="search-avatar">${item.iniciales}</div>
                    <div style="flex:1; overflow:hidden;">
                        <div style="font-weight:700; font-size:0.85rem; color:#f1f5f9; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${item.nombre}</div>
                        <div style="font-size:0.72rem; color:var(--text-muted);">Doc: ${item.doc} · Ficha ${item.ficha}</div>
                    </div>
                    <span style="font-size:0.65rem; font-weight:700; color:${estadoColors[item.estado] || '#94a3b8'}; flex-shrink:0;">●</span>
                </a>
            `).join('');
            searchResults.classList.add('open');
        }

        // Cerrar al hacer clic afuera
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('open');
            }
        });

        // Navegar con teclado
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                searchResults.classList.remove('open');
                searchInput.blur();
            }
        });

        // ============ CONTADOR ANIMADO ============
        function animateCounter(element, target, duration = 1200) {
            const start = performance.now();
            const startVal = 0;
            function update(time) {
                const elapsed = time - start;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
                element.textContent = Math.round(startVal + (target - startVal) * eased);
                if (progress < 1) requestAnimationFrame(update);
            }
            requestAnimationFrame(update);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-counter]').forEach(el => {
                const target = parseInt(el.dataset.counter, 10);
                animateCounter(el, target);
            });
        });
    </script>

    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
    </style>

</body>
</html>
