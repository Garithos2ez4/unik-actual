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
        const selectAgencia = document.getElementById('select-agencia');
        const selectDestino = document.getElementById('select-destino');
        const selectSubAgencia = document.getElementById('select-subagencia');
        
        const idAgencia = selectAgencia.value;
        const idDestino = selectDestino.value;

        if (!idAgencia || !idDestino) {
            selectSubAgencia.innerHTML = '<option value="">Primero elija Agencia y Distrito...</option>';
            selectSubAgencia.disabled = true;
            return;
        }

        selectSubAgencia.innerHTML = '<option value="">Cargando oficinas...</option>';
        selectSubAgencia.disabled = true;

        fetch(`/formulario-envio/api/subagencias/${idAgencia}/${idDestino}`)
            .then(r => r.json())
            .then(data => {
                let html = '<option value="">Seleccione oficina...</option>';
                data.forEach(sub => {
                    const partes = sub.nombre_oficina.split(' / ');
                    const nombreTerminal = partes[partes.length - 1];
                    html += `<option value="${sub.idSubAgencia}">${nombreTerminal} - ${sub.direccion}</option>`;
                });
                selectSubAgencia.innerHTML = html;
                selectSubAgencia.disabled = false;
            })
            .catch(error => {
                console.error("Error al cargar subagencias: ", error);
                selectSubAgencia.innerHTML = '<option value="">Error al cargar</option>';
            });
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

                        let hasEnvio = false;
                        if (data.ultimo_envio) {
                            hasEnvio = true;
                            const env = data.ultimo_envio;

                            if (env.idAgencia) document.getElementById('select-agencia').value = env.idAgencia;

                            const chkDomicilio = document.getElementById('entrega_domicilio');
                            chkDomicilio.checked = (env.entrega_domicilio == 1);
                            chkDomicilio.dispatchEvent(new Event('change'));

                            if (env.dir) document.querySelector('input[name="dir"]').value = env.dir;
                            if (env.ref) document.querySelector('input[name="ref"]').value = env.ref;

                            if (env.idDepartamento) {
                                document.getElementById('select-departamento').value = env.idDepartamento;
                                fetch(`/formulario-envio/api/provincias/${env.idDepartamento}`)
                                    .then(r => r.json())
                                    .then(provData => {
                                        const selProv = document.getElementById('select-provincia');
                                        selProv.innerHTML = '<option value="">Seleccione provincia...</option>';
                                        provData.forEach(p => {
                                            selProv.innerHTML += `<option value="${p.idProvincia}">${p.nombre}</option>`;
                                        });
                                        selProv.disabled = false;
                                        if (env.idProvincia) selProv.value = env.idProvincia;

                                        if (env.idProvincia) {
                                            return fetch(`/formulario-envio/api/destinos/${env.idProvincia}`);
                                        }
                                    })
                                    .then(r => {
                                        if (r) return r.json();
                                    })
                                    .then(destData => {
                                        if (destData) {
                                            const selDest = document.getElementById('select-destino');
                                            selDest.innerHTML = '<option value="">Seleccione distrito...</option>';
                                            destData.forEach(d => {
                                                selDest.innerHTML += `<option value="${d.idDestino}">${d.nombre}</option>`;
                                            });
                                            selDest.disabled = false;
                                            if (env.idDestino) {
                                                selDest.value = env.idDestino;
                                                // Cargar subagencias después de seleccionar el destino
                                                if (env.idAgencia) {
                                                    return fetch(`/formulario-envio/api/subagencias/${env.idAgencia}/${env.idDestino}`);
                                                }
                                            }
                                        }
                                    })
                                    .then(r => {
                                        if (r) return r.json();
                                    })
                                    .then(subData => {
                                        if (subData) {
                                            const selSub = document.getElementById('select-subagencia');
                                            selSub.innerHTML = '<option value="">Seleccione oficina...</option>';
                                            subData.forEach(s => {
                                                 const partes = s.nombre_oficina.split(' / ');
                                                 const nombreTerminal = partes[partes.length - 1];
                                                 selSub.innerHTML += `<option value="${s.idSubAgencia}">${nombreTerminal} - ${s.direccion}</option>`;
                                            });
                                            selSub.disabled = false;
                                            if (env.idSubAgencia) selSub.value = env.idSubAgencia;
                                        }
                                    })
                                    .catch(err => console.log('Error loading ubigeo cascade:', err));
                            }
                        }

                        // Notificación
                        Swal.fire({
                            icon: 'success',
                            title: '¡Bienvenido de nuevo!',
                            text: hasEnvio ? 'Gracias por tu preferencia, hemos autocompletado tus datos personales y de envío usando tu última compra para ahorrarte tiempo.' : 'Hemos encontrado tus datos personales y los hemos autocompletado.',
                            confirmButtonColor: '#00b1b9'
                        });
                    }
                })
                .catch(err => console.log('Error buscando cliente:', err));
        }
    });

    document.getElementById('form-envio-publico').addEventListener('submit', function(e) {
        const campos = [{
                id: 'idTipoDocumento',
                label: 'Tipo de Documento'
            },
            {
                id: 'numeroDocumento',
                label: 'N° de Documento'
            },
            {
                id: 'nombre',
                label: 'Nombres'
            },
            {
                id: 'apellidoPaterno',
                label: 'Apellido Paterno'
            },
            {
                id: 'telefono',
                label: 'Celular / Teléfono'
            },
            {
                id: 'select-destino',
                label: 'Distrito'
            },
            {
                id: 'select-agencia',
                label: 'Agencia'
            },
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