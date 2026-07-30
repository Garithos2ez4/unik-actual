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
        const containerSub = document.getElementById('container-subagencia');
        
        const idAgencia = selectAgencia.value;
        const idDestino = selectDestino.value;

        if (!idAgencia || !idDestino) {
            selectSubAgencia.innerHTML = '<option value="">Primero elija Agencia y Distrito...</option>';
            selectSubAgencia.disabled = true;
            if (containerSub) containerSub.style.display = 'none';
            return;
        }

        selectSubAgencia.innerHTML = '<option value="">Cargando oficinas...</option>';
        selectSubAgencia.disabled = true;

        fetch(`/formulario-envio/api/subagencias/${idAgencia}/${idDestino}`)
            .then(r => r.json())
            .then(data => {
                if (containerSub) {
                    if (!data || data.length === 0) {
                        containerSub.style.display = 'none';
                        selectSubAgencia.innerHTML = '<option value="">Sin oficinas disponibles</option>';
                        selectSubAgencia.value = '';
                    } else {
                        containerSub.style.display = 'block';
                        let html = '<option value="">Seleccione oficina...</option>';
                        data.forEach(sub => {
                            const partes = sub.nombre_oficina.split(' / ');
                            const nombreTerminal = partes[partes.length - 1];
                            html += `<option value="${sub.idSubAgencia}">${nombreTerminal} - ${sub.direccion}</option>`;
                        });
                        selectSubAgencia.innerHTML = html;
                        selectSubAgencia.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error("Error al cargar subagencias: ", error);
                selectSubAgencia.innerHTML = '<option value="">Error al cargar</option>';
            });
    }

    // ─── Mostrar/Ocultar sección Persona que Recibe ───────────
    function toggleSeccionReceptor() {
        const seccion = document.getElementById('seccion-receptor');
        const selectAgencia = document.getElementById('select-agencia');
        const selectDoc = document.getElementById('idTipoDocumento');
        
        // Shalom = idAgencia 1, RUC = idTipoDocumento 3
        const esShalom = selectAgencia && selectAgencia.value === '1';
        const esRuc = selectDoc && selectDoc.value === '3';
        
        // 1. Mostrar/Ocultar sección receptor (Shalom + RUC)
        if (esShalom && esRuc) {
            seccion.style.display = 'block';
        } else {
            seccion.style.display = 'none';
            // Limpiar campos al ocultar
            document.getElementById('receptor_nombre').value = '';
            document.getElementById('receptor_dni').value = '';
            document.getElementById('receptor_telefono').value = '';
        }

        // 2. Mostrar/Ocultar Apellidos y cambiar label Nombres
        const divPaterno = document.getElementById('div-apellido-paterno');
        const divMaterno = document.getElementById('div-apellido-materno');
        const labelNombre = document.getElementById('label-nombre');
        
        if (esRuc) {
            if (divPaterno) divPaterno.style.display = 'none';
            if (divMaterno) divMaterno.style.display = 'none';
            if (labelNombre) labelNombre.innerHTML = 'Razón Social <span class="required-star">*</span>';
            // Opcionalmente limpiar apellidos
            document.getElementById('apellidoPaterno').value = '';
            document.getElementById('apellidoPaterno').removeAttribute('required');
            document.getElementById('apellidoMaterno').value = '';
        } else {
            if (divPaterno) divPaterno.style.display = 'block';
            if (divMaterno) divMaterno.style.display = 'block';
            if (labelNombre) labelNombre.innerHTML = 'Nombres <span class="required-star">*</span>';
            document.getElementById('apellidoPaterno').setAttribute('required', 'required');
        }
    }

    // Escuchar cambios en agencia y tipo de documento
    document.getElementById('select-agencia').addEventListener('change', toggleSeccionReceptor);
    document.getElementById('idTipoDocumento').addEventListener('change', toggleSeccionReceptor);

    // ─── Autocompletar Cliente por Documento ───────────────────
    document.getElementById('numeroDocumento').addEventListener('input', function(e) {
        let val = e.target.value.trim();
        
        // Desbloquear campos por defecto mientras escribe
        const fieldsToLock = ['nombre', 'apellidoPaterno', 'apellidoMaterno'];
        fieldsToLock.forEach(id => {
            let el = document.getElementById(id);
            if (el) {
                el.readOnly = false;
                el.classList.remove('bg-light');
            }
        });

        // Buscar solo si el documento tiene 8 (DNI) o 11 (RUC) caracteres
        if (val.length === 8 || val.length === 11) {
            fetch(`/formulario-envio/api/buscar-cliente/${val}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const c = data.cliente;
                        if (c.idTipoDocumento) document.getElementById('idTipoDocumento').value = c.idTipoDocumento;
                        
                        if (c.nombre) {
                            let el = document.getElementById('nombre');
                            el.value = c.nombre;
                            el.readOnly = true;
                            el.classList.add('bg-light');
                        }
                        if (c.apellidoPaterno) {
                            let el = document.getElementById('apellidoPaterno');
                            el.value = c.apellidoPaterno;
                            el.readOnly = true;
                            el.classList.add('bg-light');
                        }
                        if (c.apellidoMaterno) {
                            let el = document.getElementById('apellidoMaterno');
                            el.value = c.apellidoMaterno;
                            el.readOnly = true;
                            el.classList.add('bg-light');
                        }
                        
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
                                        const containerSub = document.getElementById('container-subagencia');
                                        const selSub = document.getElementById('select-subagencia');
                                        if (subData && subData.length > 0) {
                                            if (containerSub) containerSub.style.display = 'block';
                                            selSub.innerHTML = '<option value="">Seleccione oficina...</option>';
                                            subData.forEach(s => {
                                                 const partes = s.nombre_oficina.split(' / ');
                                                 const nombreTerminal = partes[partes.length - 1];
                                                 selSub.innerHTML += `<option value="${s.idSubAgencia}">${nombreTerminal} - ${s.direccion}</option>`;
                                            });
                                            selSub.disabled = false;
                                            if (env.idSubAgencia) selSub.value = env.idSubAgencia;
                                        } else {
                                            if (containerSub) containerSub.style.display = 'none';
                                            selSub.innerHTML = '<option value="">Sin oficinas disponibles</option>';
                                            selSub.value = '';
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

        // Validar campos de receptor si la sección está visible
        const seccionReceptor = document.getElementById('seccion-receptor');
        if (seccionReceptor && seccionReceptor.style.display !== 'none') {
            const receptorNombre = document.getElementById('receptor_nombre').value.trim();
            const receptorDni = document.getElementById('receptor_dni').value.trim();
            let faltantesReceptor = [];
            if (!receptorNombre || receptorNombre.length < 3) faltantesReceptor.push('Nombre del receptor (mín. 3 letras)');
            if (!receptorDni || receptorDni.length !== 8) faltantesReceptor.push('DNI del receptor (8 dígitos)');
            
            if (faltantesReceptor.length > 0) {
                e.preventDefault();
                if (!receptorNombre || receptorNombre.length < 3) document.getElementById('receptor_nombre').style.borderColor = '#ef4444';
                if (!receptorDni || receptorDni.length !== 8) document.getElementById('receptor_dni').style.borderColor = '#ef4444';
                Swal.fire({
                    icon: 'error',
                    title: 'Datos del receptor incompletos',
                    html: 'Shalom requiere los datos de quien recibirá el paquete:<br><b>' + faltantesReceptor.join(', ') + '</b>',
                    confirmButtonColor: '#00b1b9'
                });
                return;
            }
        }

        const telefonoVal = document.getElementById('telefono').value.trim();
        if (telefonoVal.length !== 9) {
            e.preventDefault();
            document.getElementById('telefono').style.borderColor = '#ef4444';
            Swal.fire({
                icon: 'error',
                title: 'Número inválido',
                text: 'El número de celular debe tener exactamente 9 dígitos.',
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