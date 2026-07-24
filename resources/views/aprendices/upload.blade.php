@extends('layouts.app')

@section('title', 'Cargue Masivo de Aprendices')

@section('content')
<div class="card" style="max-width: 720px; margin: 0 auto; padding: 3rem; position: relative; overflow: hidden;">
    <!-- Decoración -->
    <div style="position:absolute;top:-60px;right:-60px;width:180px;height:180px;background:var(--primary-glow);filter:blur(70px);opacity:0.25;"></div>

    <div style="text-align:center; margin-bottom:2.5rem;">
        <div style="width:80px;height:80px;background:rgba(57,169,0,0.1);border-radius:24px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;border:1px solid rgba(57,169,0,0.2);">
            <i class="fa-solid fa-cloud-arrow-up" style="font-size:2rem;color:var(--primary);"></i>
        </div>
        <h2 style="font-size:1.75rem;font-weight:800;color:#fff;margin-bottom:0.5rem;">Carga Masiva de Datos</h2>
        <p style="color:var(--text-muted);font-size:0.9rem;max-width:440px;margin:0 auto;">
            Sube el reporte exportado desde <strong>Sofia Plus</strong> para actualizar automáticamente el estado de los aprendices.
        </p>
    </div>

    <form id="upload-form" action="{{ route('aprendices.import') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Selector de ficha -->
        <div style="margin-bottom:1.75rem;">
            <label for="Id_Ficha" style="display:block;font-size:0.72rem;font-weight:700;color:var(--text-muted);margin-bottom:0.6rem;text-transform:uppercase;letter-spacing:0.05em;">Ficha Destino (Opcional)</label>
            <select name="Id_Ficha" id="Id_Ficha" class="form-control" style="padding:0.85rem 1.2rem;font-size:0.95rem;">
                <option value="">— Autodetectar desde el archivo —</option>
                @foreach($fichas as $ficha)
                    <option value="{{ $ficha->Id_Ficha }}">{{ $ficha->Id_Ficha }} — {{ $ficha->programa->Nombre }}</option>
                @endforeach
            </select>
            <p style="font-size:0.72rem;color:var(--text-muted);margin-top:0.5rem;padding-left:0.25rem;">
                <i class="fa-solid fa-circle-info"></i> Si se deja vacío, el sistema detectará la ficha automáticamente.
            </p>
        </div>

        <!-- Zona Drag & Drop -->
        <div style="margin-bottom:2rem;">
            <label style="display:block;font-size:0.72rem;font-weight:700;color:var(--text-muted);margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">Archivo del Reporte</label>

            <div id="drop-zone"
                 style="border:2px dashed rgba(255,255,255,0.15);border-radius:24px;padding:3rem 2rem;text-align:center;background:rgba(0,0,0,0.1);transition:all 0.3s;cursor:pointer;position:relative;"
                 onclick="document.getElementById('archivo_excel').click()">

                <input type="file" name="archivo_excel" id="archivo_excel" accept=".xlsx,.xls,.csv" required style="display:none;">

                <!-- Estado: Sin archivo -->
                <div id="drop-empty">
                    <i class="fa-solid fa-file-excel" id="drop-icon" style="font-size:2.75rem;color:var(--primary);margin-bottom:1rem;opacity:0.7;transition:transform 0.3s;display:block;"></i>
                    <div style="font-weight:700;color:#fff;font-size:1.05rem;margin-bottom:0.3rem;">Arrastra tu archivo aquí</div>
                    <div style="color:var(--text-muted);font-size:0.85rem;">o haz clic para seleccionar</div>
                    <div style="margin-top:1rem;display:flex;gap:0.5rem;justify-content:center;flex-wrap:wrap;">
                        @foreach(['.xlsx', '.xls', '.csv'] as $ext)
                        <span style="background:rgba(57,169,0,0.1);color:var(--primary);border:1px solid rgba(57,169,0,0.2);padding:0.25rem 0.6rem;border-radius:6px;font-size:0.7rem;font-weight:700;">{{ $ext }}</span>
                        @endforeach
                    </div>
                </div>

                <!-- Estado: Archivo seleccionado -->
                <div id="drop-selected" style="display:none;">
                    <div id="file-icon-wrap" style="width:64px;height:64px;background:rgba(57,169,0,0.1);border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;border:2px solid rgba(57,169,0,0.3);">
                        <i class="fa-solid fa-file-excel" style="font-size:1.75rem;color:var(--primary);"></i>
                    </div>
                    <div id="file-name" style="font-weight:700;color:#fff;font-size:1rem;margin-bottom:0.25rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:380px;margin-left:auto;margin-right:auto;"></div>
                    <div id="file-size" style="font-size:0.8rem;color:var(--text-muted);"></div>
                    <button type="button" onclick="clearFile(event)" style="margin-top:1rem;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#fca5a5;padding:0.4rem 1rem;border-radius:8px;cursor:pointer;font-size:0.8rem;font-weight:600;font-family:inherit;transition:all 0.2s;"
                            onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                        <i class="fa-solid fa-xmark"></i> Quitar archivo
                    </button>
                </div>
            </div>
        </div>

        <!-- Botón submit -->
        <button type="submit" id="submit-btn" class="btn btn-primary" style="width:100%;justify-content:center;padding:1.1rem;font-size:1rem;letter-spacing:0.025em;">
            <i class="fa-solid fa-bolt" id="btn-icon"></i>
            <span id="btn-text">PROCESAR IMPORTACIÓN</span>
        </button>

        <!-- Barra de progreso (fake, UX) -->
        <div id="progress-wrap" style="display:none;margin-top:1.5rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                <span style="font-size:0.8rem;color:var(--text-muted);">Procesando archivo...</span>
                <span id="progress-pct" style="font-size:0.8rem;font-weight:700;color:var(--primary);">0%</span>
            </div>
            <div style="height:8px;background:rgba(255,255,255,0.05);border-radius:4px;overflow:hidden;">
                <div id="progress-bar" style="height:100%;width:0%;background:linear-gradient(to right,var(--primary),#4ade80);border-radius:4px;transition:width 0.3s;"></div>
            </div>
            <p style="font-size:0.75rem;color:var(--text-muted);margin-top:0.75rem;text-align:center;">
                <i class="fa-solid fa-spinner fa-spin"></i> Esto puede tomar unos segundos según el tamaño del archivo...
            </p>
        </div>
    </form>
</div>

<script>
    const dropZone  = document.getElementById('drop-zone');
    const fileInput = document.getElementById('archivo_excel');

    // Drag & Drop
    ['dragenter', 'dragover'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropZone.style.borderColor   = 'var(--primary)';
            dropZone.style.background    = 'rgba(57,169,0,0.06)';
            dropZone.style.transform     = 'scale(1.01)';
            document.getElementById('drop-icon') && (document.getElementById('drop-icon').style.transform = 'scale(1.15) translateY(-4px)');
        });
    });
    ['dragleave', 'drop'].forEach(evt => {
        dropZone.addEventListener(evt, (e) => {
            e.preventDefault();
            dropZone.style.borderColor   = 'rgba(255,255,255,0.15)';
            dropZone.style.background    = 'rgba(0,0,0,0.1)';
            dropZone.style.transform     = 'scale(1)';
            const icon = document.getElementById('drop-icon');
            if (icon) icon.style.transform = '';
        });
    });
    dropZone.addEventListener('drop', (e) => {
        const file = e.dataTransfer.files[0];
        if (file) setFile(file);
    });

    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) setFile(file);
    });

    function setFile(file) {
        document.getElementById('file-name').textContent = file.name;
        document.getElementById('file-size').textContent = (file.size / 1024).toFixed(1) + ' KB';
        document.getElementById('drop-empty').style.display    = 'none';
        document.getElementById('drop-selected').style.display = 'block';
        dropZone.style.borderColor = 'rgba(57,169,0,0.4)';
        dropZone.style.background  = 'rgba(57,169,0,0.04)';
    }

    function clearFile(e) {
        e.stopPropagation();
        fileInput.value = '';
        document.getElementById('drop-empty').style.display    = 'block';
        document.getElementById('drop-selected').style.display = 'none';
        dropZone.style.borderColor = 'rgba(255,255,255,0.15)';
        dropZone.style.background  = 'rgba(0,0,0,0.1)';
    }

    // Barra de progreso al enviar
    document.getElementById('upload-form').addEventListener('submit', function () {
        const btn      = document.getElementById('submit-btn');
        const icon     = document.getElementById('btn-icon');
        const text     = document.getElementById('btn-text');
        const wrap     = document.getElementById('progress-wrap');
        const bar      = document.getElementById('progress-bar');
        const pctEl    = document.getElementById('progress-pct');

        btn.disabled       = true;
        btn.style.opacity  = '0.7';
        icon.className     = 'fa-solid fa-spinner fa-spin';
        text.textContent   = 'Procesando...';
        wrap.style.display = 'block';

        // Progreso fake hasta el 90%
        let pct = 0;
        const interval = setInterval(() => {
            if (pct >= 88) { clearInterval(interval); return; }
            pct += Math.random() * 4;
            bar.style.width  = pct + '%';
            pctEl.textContent = Math.round(pct) + '%';
        }, 300);
    });
</script>
@endsection
