@extends('layouts.public')

@section('title', 'Datos de Envío - Unik Technology')

@section('content')
<div class="public-card">
    <div class="public-card-header">
        <h3><i class="bi bi-truck"></i> Datos de Envío</h3>
        <p>Completa el formulario para que podamos enviarte tu pedido</p>
        @if($segundosRestantes !== null)
        <div class="timer-badge" id="timer-badge">
            <i class="bi bi-clock"></i>
            <span id="timer-text">--:--</span>
        </div>
        @endif
    </div>

    <div class="public-card-body">
        <form id="form-envio-publico" action="{{ route('formulario.publico.store', $token) }}" method="POST" novalidate>
            @csrf

            {{-- ═══════ SECCIÓN 1: DATOS PERSONALES ═══════ --}}
            <div class="section-title">
                <i class="bi bi-person-fill"></i> Tus Datos Personales
            </div>

            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <label class="form-label">Tipo de Documento <span class="required-star">*</span></label>
                    <select name="idTipoDocumento" id="idTipoDocumento" class="form-select" required>
                        <option value="">Seleccione...</option>
                        @foreach($documentos as $doc)
                            <option value="{{ $doc->idTipoDocumento }}">{{ $doc->descripcion }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6">
                    <label class="form-label">N° de Documento <span class="required-star">*</span></label>
                    <input type="text" name="numeroDocumento" id="numeroDocumento" class="form-control" placeholder="Ej: 72345678" required maxlength="20" inputmode="numeric">
                </div>

                <div class="col-12 col-sm-6">
                    <label class="form-label">Nombres <span class="required-star">*</span></label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Tus nombres" required maxlength="100">
                </div>

                <div class="col-12 col-sm-6">
                    <label class="form-label">Apellido Paterno <span class="required-star">*</span></label>
                    <input type="text" name="apellidoPaterno" id="apellidoPaterno" class="form-control" placeholder="Apellido paterno" required maxlength="100">
                </div>

                <div class="col-12 col-sm-6">
                    <label class="form-label">Apellido Materno</label>
                    <input type="text" name="apellidoMaterno" id="apellidoMaterno" class="form-control" placeholder="Apellido materno" maxlength="100">
                </div>

                <div class="col-12 col-sm-6">
                    <label class="form-label">Celular / Teléfono <span class="required-star">*</span></label>
                    <input type="tel" name="telefono" id="telefono" class="form-control" placeholder="Ej: 987654321" required maxlength="20" inputmode="tel">
                </div>

                <div class="col-12">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="correo" id="correo" class="form-control" placeholder="correo@ejemplo.com" maxlength="100" inputmode="email">
                </div>
            </div>

            {{-- ═══════ SECCIÓN 2: DESTINO DEL ENVÍO ═══════ --}}
            <div class="section-title mt-4">
                <i class="bi bi-geo-alt-fill"></i> Destino del Envío
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Departamento <span class="required-star">*</span></label>
                    <select id="select-departamento" class="form-select" required onchange="cargarProvincias(this.value)">
                        <option value="">Seleccione departamento...</option>
                        @foreach($departamentos as $depto)
                            <option value="{{ $depto->idDepartamento }}">{{ $depto->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6">
                    <label class="form-label">Provincia <span class="required-star">*</span></label>
                    <select id="select-provincia" class="form-select" required disabled onchange="cargarDestinos(this.value)">
                        <option value="">Primero elija departamento...</option>
                    </select>
                </div>

                <div class="col-12 col-sm-6">
                    <label class="form-label">Distrito <span class="required-star">*</span></label>
                    <select name="idDestino" id="select-destino" class="form-select" required disabled onchange="cargarSubAgencias()">
                        <option value="">Primero elija provincia...</option>
                    </select>
                </div>
            </div>

            {{-- ═══════ SECCIÓN 3: AGENCIA DE TRANSPORTE ═══════ --}}
            <div class="section-title mt-4">
                <i class="bi bi-building"></i> Agencia de Transporte
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Agencia <span class="required-star">*</span></label>
                    <select name="idAgencia" id="select-agencia" class="form-select" required onchange="cargarSubAgencias()">
                        <option value="">Seleccione agencia...</option>
                        @foreach($agencias as $agencia)
                            <option value="{{ $agencia->idAgencia }}">{{ $agencia->nombre }}</option>
                        @endforeach
                    </select>
                </div>


                <div class="col-12 mt-2">
                    <div class="form-check form-switch p-3 rounded" style="background-color: #f8f9fa; border: 1px solid #e2e8f0;">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="entrega_domicilio" name="entrega_domicilio" value="1">
                        <label class="form-check-label fw-bold text-primary" for="entrega_domicilio">¿Entrega a Domicilio?</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label" id="label-dir">Dirección de entrega</label>
                    <input type="text" name="dir" class="form-control" placeholder="Ej: Av. Principal 123" maxlength="100">
                </div>

                <div class="col-12">
                    <label class="form-label">Referencia</label>
                    <input type="text" name="ref" class="form-control" placeholder="Ej: Cerca al parque central" maxlength="100">
                </div>
            </div>

            {{-- ═══════ BOTÓN ENVIAR ═══════ --}}
            <div class="mt-4">
                <button type="submit" class="btn btn-submit" id="btn-enviar">
                    <i class="bi bi-send-fill"></i> Enviar mis datos
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ─── Timer de expiración ───────────────────────────────────
    @if($segundosRestantes !== null)
    (function() {
        let segundos = {{ max(0, $segundosRestantes) }};
        const timerText = document.getElementById('timer-text');
        const timerBadge = document.getElementById('timer-badge');

        function actualizar() {
            if (segundos <= 0) {
                timerText.textContent = 'Expirado';
                timerBadge.classList.add('expired');
                document.getElementById('btn-enviar').disabled = true;
                document.getElementById('btn-enviar').innerHTML = '<i class="bi bi-lock-fill"></i> Tiempo expirado';
                Swal.fire({
                    icon: 'warning',
                    title: 'Tiempo agotado',
                    text: 'Este formulario ha expirado. Solicita un nuevo enlace a tu vendedor.',
                    confirmButtonColor: '#00b1b9'
                });
                return;
            }

            const min = Math.floor(segundos / 60);
            const seg = segundos % 60;
            timerText.textContent = `${min.toString().padStart(2, '0')}:${seg.toString().padStart(2, '0')}`;

            if (segundos <= 120) {
                timerBadge.style.background = 'rgba(239, 68, 68, 0.2)';
                timerBadge.style.color = '#fca5a5';
            }

            segundos--;
            setTimeout(actualizar, 1000);
        }

        actualizar();
    })();
    @endif

    // ─── Cascada Ubigeo (AJAX) ─────────────────────────────────
    function cargarProvincias(idDepartamento) {
        const select = document.getElementById('select-provincia');
        const selectDestino = document.getElementById('select-destino');
        const selectSub = document.getElementById('select-subagencia');

        select.innerHTML = '<option value="">Cargando...</option>';
        select.disabled = true;
        selectDestino.innerHTML = '<option value="">Primero elija provincia...</option>';
        selectDestino.disabled = true;

        if (!idDepartamento) {
            select.innerHTML = '<option value="">Primero elija departamento...</option>';
            return;
        }

        fetch(`/formulario-envio/api/provincias/${idDepartamento}`)
            .then(r => r.json())
            .then(data => {
                select.innerHTML = '<option value="">Seleccione provincia...</option>';
                data.forEach(p => {
                    select.innerHTML += `<option value="${p.idProvincia}">${p.nombre}</option>`;
                });
                select.disabled = false;
            })
            .catch(() => {
                select.innerHTML = '<option value="">Error al cargar</option>';
            });
    }

    function cargarDestinos(idProvincia) {
        const select = document.getElementById('select-destino');
        const selectSub = document.getElementById('select-subagencia');

        select.innerHTML = '<option value="">Cargando...</option>';
        select.disabled = true;

        if (!idProvincia) {
            select.innerHTML = '<option value="">Primero elija provincia...</option>';
            return;
        }

        fetch(`/formulario-envio/api/destinos/${idProvincia}`)
            .then(r => r.json())
            .then(data => {
                select.innerHTML = '<option value="">Seleccione distrito...</option>';
                data.forEach(d => {
                    select.innerHTML += `<option value="${d.idDestino}">${d.nombre}</option>`;
                });
                select.disabled = false;
            })
            .catch(() => {
                select.innerHTML = '<option value="">Error al cargar</option>';
            });
    }

    function cargarSubAgencias() {
        // La funcionalidad de SubAgencias fue removida del formulario público.
    }

    // ─── Autocompletar Cliente por Documento ───────────────────
    document.getElementById('numeroDocumento').addEventListener('input', function(e) {
        let val = e.target.value.trim();
        // Buscar solo si el documento tiene 8 (DNI) o 11 (RUC) caracteres
        if (val.length === 8 || val.length === 11) {
            fetch(`/formulario-envio/api/buscar-cliente/${val}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const c = data.cliente;
                        if (c.idTipoDocumento) document.getElementById('idTipoDocumento').value = c.idTipoDocumento;
                        if (c.nombre) document.getElementById('nombre').value = c.nombre;
                        if (c.apellidoPaterno) document.getElementById('apellidoPaterno').value = c.apellidoPaterno;
                        if (c.apellidoMaterno) document.getElementById('apellidoMaterno').value = c.apellidoMaterno;
                        if (c.telefono) document.getElementById('telefono').value = c.telefono;
                        if (c.correo) document.getElementById('correo').value = c.correo;

                        // Notificación opcional
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Datos cargados automáticamente',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                })
                .catch(err => console.log('Error buscando cliente:', err));
        }
    });

    // ─── Validación del formulario ─────────────────────────────
    document.getElementById('form-envio-publico').addEventListener('submit', function(e) {
        const campos = [
            { id: 'idTipoDocumento', label: 'Tipo de Documento' },
            { id: 'numeroDocumento', label: 'N° de Documento' },
            { id: 'nombre', label: 'Nombres' },
            { id: 'apellidoPaterno', label: 'Apellido Paterno' },
            { id: 'telefono', label: 'Celular / Teléfono' },
            { id: 'select-destino', label: 'Distrito' },
            { id: 'select-agencia', label: 'Agencia' },
        ];

        let faltantes = [];
        campos.forEach(c => {
            const el = document.getElementById(c.id);
            if (!el || !el.value || el.value === '') {
                faltantes.push(c.label);
                el.style.borderColor = '#ef4444';
            } else {
                el.style.borderColor = '';
            }
        });

        if (faltantes.length > 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Campos incompletos',
                html: 'Por favor completa:<br><b>' + faltantes.join(', ') + '</b>',
                confirmButtonColor: '#00b1b9'
            });
            return;
        }

        // Deshabilitar botón para evitar doble envío
        const btn = document.getElementById('btn-enviar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enviando...';
    });

    document.getElementById('entrega_domicilio').addEventListener('change', function() {
        const labelDir = document.getElementById('label-dir');
        if (this.checked) {
            labelDir.textContent = 'Dirección Exacta';
        } else {
            labelDir.textContent = 'Dirección de entrega';
        }
    });
</script>
@endpush
