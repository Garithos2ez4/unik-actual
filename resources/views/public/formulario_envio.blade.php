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

    @if($esDespues5pm)
    <div class="alert alert-info d-flex align-items-start gap-2 mx-3 mt-3 mb-0" style="border-radius: 10px; font-size: 0.92rem;">
        <i class="bi bi-info-circle-fill fs-5 mt-1 flex-shrink-0"></i>
        <div>
            <strong>Información sobre tu pedido:</strong><br>
            Tu pedido será registrado en la agencia de transporte el
            <strong>{{ $fechaRegistroReal }}</strong>,
            ya que este formulario fue generado después del horario de atención (5:00 PM, hora Perú).
        </div>
    </div>
    @endif

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
                    <input type="text" name="numeroDocumento" id="numeroDocumento" class="form-control" placeholder="Ej: 72345678" required maxlength="20" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>

                <div class="col-12 col-sm-6">
                    <label class="form-label" id="label-nombre">Nombres <span class="required-star">*</span></label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Tus nombres" required maxlength="100">
                </div>

                <div class="col-12 col-sm-6" id="div-apellido-paterno">
                    <label class="form-label">Apellido Paterno <span class="required-star">*</span></label>
                    <input type="text" name="apellidoPaterno" id="apellidoPaterno" class="form-control" placeholder="Apellido paterno" required maxlength="100">
                </div>

                <div class="col-12 col-sm-6" id="div-apellido-materno">
                    <label class="form-label">Apellido Materno</label>
                    <input type="text" name="apellidoMaterno" id="apellidoMaterno" class="form-control" placeholder="Apellido materno" maxlength="100">
                </div>

                <div class="col-12 col-sm-6">
                    <label class="form-label">Celular / Teléfono <span class="required-star">*</span></label>
                    <input type="tel" name="telefono" id="telefono" class="form-control" placeholder="Ej: 987654321" required minlength="9" maxlength="9" pattern="[0-9]{9}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="mismo_numero" checked>
                        <label class="form-check-label text-muted" for="mismo_numero" style="font-size: 0.85rem;">
                            ¿Te comunicaste con nosotros desde este mismo número?
                        </label>
                    </div>
                </div>

                <div class="col-12 col-sm-6" id="div-numero-registrante" style="display: none;">
                    <label class="form-label text-primary fw-bold">Tu N° de WhatsApp <span class="required-star">*</span></label>
                    <input type="tel" name="telefono_registrante" id="telefono_registrante" class="form-control border-primary" placeholder="Número con el que nos escribiste" minlength="9" maxlength="9" pattern="[0-9]{9}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)">
                </div>

                <div class="col-12">
                    <label class="form-label">Correo Electrónico-(Opcional)</label>
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
                    <select name="idAgencia" id="select-agencia" class="form-select" required onchange="verificarCostoAgencia(); cargarSubAgencias()">
                        <option value="">Seleccione agencia...</option>
                        @foreach($agencias as $agencia)
                        <option value="{{ $agencia->idAgencia }}">{{ $agencia->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 mt-2" id="container-subagencia" style="display:none;">
                    <label class="form-label fw-bold">Oficina / Sucursal <span class="text-muted">(Opcional)</span></label>
                    <select name="idSubAgencia" id="select-subagencia" class="form-select" disabled>
                        <option value="">Primero elija Agencia y Distrito...</option>
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

            {{-- ═══════ SECCIÓN 4: PERSONA QUE RECIBE (Shalom + RUC) ═══════ --}}
            <div id="seccion-receptor" style="display: none;">
                <div class="section-title mt-4">
                    <i class="bi bi-person-check-fill"></i> Persona que Recibe
                    <small class="d-block text-muted mt-1" style="font-size: 0.82rem;">Shalom requiere datos de una persona natural con DNI para retirar el paquete</small>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label">Nombre completo del receptor <span class="required-star">*</span></label>
                        <input type="text" name="receptor[nombre]" id="receptor_nombre" class="form-control" placeholder="Ej: Juan Pérez García" maxlength="150" minlength="3">
                    </div>

                    <div class="col-12 col-sm-3">
                        <label class="form-label">DNI del receptor <span class="required-star">*</span></label>
                        <input type="text" name="receptor[dni]" id="receptor_dni" class="form-control" placeholder="Ej: 72345678" maxlength="8" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8)">
                    </div>

                    <div class="col-12 col-sm-3">
                        <label class="form-label">Teléfono <span class="text-muted">(Opcional)</span></label>
                        <input type="tel" name="receptor[telefono]" id="receptor_telefono" class="form-control" placeholder="987654321" maxlength="9" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 9)">
                    </div>
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

@include('public.logic.formulario_envio')