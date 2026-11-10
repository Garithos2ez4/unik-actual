@extends('layouts.app')

@section('title', 'Seguimiento Laptops ML US')

@section('content')
<style>
    @include('laptops.styles.seguimientostyle');
</style>
<div class="container-fluid py-3 px-3">
    <!-- Cabecera Principal -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-primary d-flex align-items-center gap-2">
                <i class="bi bi-laptop"></i> Tablero de Seguimiento: Laptops ML US
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-6">
                    {{ $stats['total'] }} modelos
                </span>
            </h4>
            <small class="text-muted">
                Disponibilidad por proveedor (<strong>CUAD · DM · ALC · ASIA</strong>), vinculación con <code>Producto</code> / <code>Inventario_Proveedor</code> y cuentas de Mercado Libre (<strong>UNIK · GAMING · UP · LIZ</strong>).
            </small>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaLaptop">
                <i class="bi bi-plus-lg me-1"></i> Agregar Modelo
            </button>

            <form action="{{ route('laptops.seguimiento.vincular') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary fw-semibold" title="Busca coincidencias por P/N o Modelo en la tabla Producto y sincroniza Inventario_Proveedor">
                    <i class="bi bi-link-45deg me-1"></i> Sincronizar con Catálogo ({{ $stats['vinculados'] }}/{{ $stats['total'] }})
                </button>
            </form>

            <button type="button" class="btn btn-sm btn-outline-success fw-semibold" data-bs-toggle="modal" data-bs-target="#modalImportarExcel">
                <i class="bi bi-file-earmark-arrow-up me-1"></i> Importar Libro11.xlsx
            </button>

            <a href="{{ route('laptops.seguimiento.exportar', request()->query()) }}" class="btn btn-sm btn-success fw-semibold">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar Excel
            </a>
        </div>
    </div>

    <!-- Leyenda Interactiva de Estados y Contadores (Igual a fila 1 de Libro11.xlsx) -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-2">
            <a href="{{ route('laptops.seguimiento.index', array_merge(request()->query(), ['estado' => 'CON_STOCK'])) }}" class="text-decoration-none">
                <div class="p-2 rounded shadow-xs text-center fw-bold" style="background-color:#66FF33; color:#000; border:1px solid #28a745;">
                    <div class="small">HAY STOCK PROV / TIENDA</div>
                    <div class="fs-5">{{ $stats['con_stock'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('laptops.seguimiento.index', array_merge(request()->query(), ['estado' => 'AGOTADO'])) }}" class="text-decoration-none">
                <div class="p-2 rounded shadow-xs text-center fw-bold" style="background-color:#FF0000; color:#FFF; border:1px solid #b02a37;">
                    <div class="small">AGOTADO EN PROVEEDORES</div>
                    <div class="fs-5">{{ $stats['agotados'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('laptops.seguimiento.index', array_merge(request()->query(), ['estado' => 'EN_TRANSITO'])) }}" class="text-decoration-none">
                <div class="p-2 rounded shadow-xs text-center fw-bold" style="background-color:#FFC000; color:#000; border:1px solid #d39e00;">
                    <div class="small">EN TRÁNSITO X LLEGAR</div>
                    <div class="fs-5">{{ $stats['en_transito'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('laptops.seguimiento.index', array_merge(request()->query(), ['estado' => 'DESHABILITADA'])) }}" class="text-decoration-none">
                <div class="p-2 rounded shadow-xs text-center fw-bold" style="background-color:#7030A0; color:#FFF; border:1px solid #4d1f70;">
                    <div class="small">DESHABILITADA EN PROV</div>
                    <div class="fs-5">{{ $stats['deshabilitadas'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('laptops.seguimiento.index', array_merge(request()->query(), ['solo_vencidos' => 1])) }}" class="text-decoration-none">
                <div class="p-2 rounded shadow-xs text-center fw-bold" style="background-color:#FFC7CE; color:#9C0006; border:1px solid #f5c2c7;">
                    <div class="small"><i class="bi bi-exclamation-triangle-fill"></i> FECHA VENCIDA (&lt; HOY)</div>
                    <div class="fs-5">{{ $stats['vencidos'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <div class="p-2 rounded shadow-xs text-center border bg-light">
                <div class="small fw-semibold text-muted">COLORES DE MARCA</div>
                <div class="d-flex justify-content-center gap-2 mt-1">
                    <span class="badge text-dark border" style="background-color:#D9D9D9;">Gris = ACER</span>
                    <span class="badge text-dark border" style="background-color:#F6C6AD;">Salmón = ASUS</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <form action="{{ route('laptops.seguimiento.index') }}" method="GET" class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold mb-1">Buscar (Modelo, P/N, CPU, GPU, Código W...)</label>
                    <input type="text" name="q" value="{{ $filtros['q'] }}" class="form-control form-control-sm" placeholder="Ej: NITRO, G614, NH.U1PAA, W06335...">
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label small fw-bold mb-1">Marca</label>
                    <select name="marca" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach(array_keys($marcasCfg) as $mName)
                        <option value="{{ $mName }}" @selected($filtros['marca']===$mName)>{{ $mName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold mb-1">Gama CPU</label>
                    <select name="gama" class="form-select form-select-sm">
                        <option value="">Todas las gamas</option>
                        @foreach($gamasCfg as $gKey => $gLabel)
                        <option value="{{ $gKey }}" @selected($filtros['gama']===$gKey)>{{ $gLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label small fw-bold mb-1">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach($estadosCfg as $eKey => $eData)
                        <option value="{{ $eKey }}" @selected($filtros['estado']===$eKey)>{{ $eData['short'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label small fw-bold mb-1">Proveedor</label>
                    <select name="idProveedor" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach($proveedoresCfg as $idP => $pData)
                        <option value="{{ $idP }}" @selected((string)$filtros['idProveedor']===(string)$idP)>{{ $pData['codigo'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label small fw-bold mb-1">Cuenta ML</label>
                    <select name="idCuentaPlataforma" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach($cuentasMlCfg as $idC => $cData)
                        <option value="{{ $idC }}" @selected((string)$filtros['idCuentaPlataforma']===(string)$idC)>{{ $cData['sigla'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-1">
                    <label class="form-label small fw-bold mb-1">Responsable</label>
                    <select name="responsable" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach($responsables as $resp)
                        <option value="{{ $resp }}" @selected($filtros['responsable']===$resp)>{{ $resp }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2 d-flex align-items-center justify-content-end gap-2">
                    <div class="form-check form-switch mb-0 me-1" title="Mostrar solo filas con fecha de revisión anterior a hoy">
                        <input class="form-check-input" type="checkbox" name="solo_vencidos" value="1" id="chkVencidos" @checked($filtros['solo_vencidos'])>
                        <label class="form-check-label small fw-semibold" for="chkVencidos">Vencidos</label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary px-3">
                        <i class="bi bi-funnel-fill"></i> Filtrar
                    </button>
                    <a href="{{ route('laptops.seguimiento.index') }}" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </form>

    <!-- Tabla Principal Agrupada (Marca -> Plataforma CPU -> Gama) -->
    <div class="table-responsive shadow-sm rounded bg-white">
        <table class="table table-bordered tabla-libro11 mb-0">
            <thead class="table-light sticky-top" style="top: 0; z-index: 10;">
                <tr class="text-center fw-bold">
                    <th style="width: 68px;">RESP.</th>
                    <th style="min-width: 195px;">MODELO</th>
                    <th style="width: 105px;">SERIE</th>
                    <th style="width: 55px;">TAM.</th>
                    <th style="width: 72px;">FREQ.</th>
                    <th style="min-width: 120px;">PROCESADOR</th>
                    <th style="width: 55px;">RAM</th>
                    <th style="width: 60px;">DISCO</th>
                    <th style="width: 90px;">VIDEO</th>
                    <th style="min-width: 120px;">P/N</th>
                    <th style="width: 60px;">STOCK</th>
                    <th style="width: 115px;">FECHA REV.</th>
                    @foreach($proveedoresCfg as $idProv => $pCfg)
                    <th style="width: 72px;" title="{{ $pCfg['nombre'] }} (idProveedor={{ $idProv }})">{{ $pCfg['codigo'] }}</th>
                    @endforeach
                    <th style="width: 50px;">unik</th>
                    <th style="min-width: 155px;">CUENTA ML</th>
                    <th style="width: 95px;">ESTADO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agrupado as $marca => $plataformas)
                @php
                $collapseId = 'marca_' . preg_replace('/[^A-Z0-9]/', '', $marca);
                @endphp
                <!-- Cabecera de Marca (Azul #0070C0) -->
                <tr class="hdr-marca" data-bs-toggle="collapse" data-bs-target=".{{ $collapseId }}">
                    <td><i class="bi bi-chevron-down"></i></td>
                    <td colspan="11">{{ $marca }}</td>
                    @foreach($proveedoresCfg as $pCfg)
                    <td class="text-center">{{ $pCfg['codigo'] }}</td>
                    @endforeach
                    <td class="text-center">unik</td>
                    <td class="text-center">CUENTA ML</td>
                    <td class="text-center">ESTADO</td>
                </tr>

                @foreach($plataformas as $cpu => $gamas)
                <!-- Cabecera Plataforma CPU (Celeste #00B0F0) -->
                <tr class="hdr-cpu collapse show {{ $collapseId }}">
                    <td></td>
                    <td colspan="18">{{ $cpu }}</td>
                </tr>

                @foreach($gamas as $gama => $modelos)
                <!-- Cabecera Gama (Magenta #F705E3) -->
                <tr class="hdr-gama collapse show {{ $collapseId }}">
                    <td></td>
                    <td colspan="18">
                        {{ $gama }}
                        <span class="badge bg-dark text-white ms-2">{{ count($modelos) }}</span>
                    </td>
                </tr>

                @foreach($modelos as $item)
                @php
                $provs = $item->proveedores_data ?? [];
                $ctasIds = $item->cuentas_ml_data['ids'] ?? [];
                $vencido = $item->estaVencido();
                @endphp
                <tr id="fila-seg-{{ $item->idLaptopSeguimiento }}"
                    class="collapse show {{ $collapseId }} fila-marca-{{ $marca }} fila-estado-{{ $item->estado_fila }}">
                    <!-- A: Responsable -->
                    <td>
                        <input type="text" class="input-inline text-center fw-bold"
                            value="{{ $item->nombre_responsable }}"
                            placeholder="-"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'responsable_texto', this.value, this)">
                    </td>

                    <!-- B: Modelo + vínculo con Producto -->
                    <td class="celda-estado">
                        <div class="d-flex align-items-center gap-1">
                            <input type="text" class="input-inline fw-bold"
                                value="{{ $item->modelo }}"
                                onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'modelo', this.value, this)">
                            @if($item->idProducto)
                            <span class="badge bg-primary" title="Vinculado a Producto #{{ $item->idProducto }} ({{ $item->producto->codigoProducto ?? '' }})">
                                <i class="bi bi-link-45deg"></i>
                            </span>
                            @endif
                        </div>
                    </td>

                    <!-- C: Serie -->
                    <td class="celda-estado">
                        <input type="text" class="input-inline"
                            value="{{ $item->serie }}"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'serie', this.value, this)">
                    </td>

                    <!-- D: Tamaño -->
                    <td class="celda-estado text-center">
                        <input type="text" class="input-inline text-center"
                            value="{{ $item->tamano }}"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'tamano', this.value, this)">
                    </td>

                    <!-- E: Frecuencia (Hz) -->
                    <td class="celda-estado text-center" style="min-width: 70px;">
                        @php
                            $valNorm = strtoupper(str_replace(' ', '', (string)$item->frecuencia));
                            $encontrado = false;
                        @endphp
                        <select class="input-inline text-center sel-frecuencia"
                                style="cursor: pointer;"
                                title="Frecuencia (clic para ver todas las opciones de Hz)"
                                onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'frecuencia', this.value, this)">
                            <option value="">-</option>
                            @foreach($frecuenciasSugeridas as $sug)
                                @php
                                    $sugNorm = strtoupper(str_replace(' ', '', (string)$sug));
                                    $esSel = ($valNorm === $sugNorm);
                                    if ($esSel) $encontrado = true;
                                @endphp
                                <option value="{{ $sug }}" @selected($esSel)>{{ $sug }}</option>
                            @endforeach
                            @if(!empty($item->frecuencia) && !$encontrado)
                                <option value="{{ $item->frecuencia }}" selected>{{ $item->frecuencia }}</option>
                            @endif
                        </select>
                    </td>

                    <!-- F: Procesador -->
                    <td class="celda-estado">
                        <input type="text" class="input-inline"
                            value="{{ $item->procesador }}"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'procesador', this.value, this)">
                    </td>

                    <!-- G: RAM -->
                    <td class="celda-estado text-center">
                        <input type="text" class="input-inline text-center"
                            value="{{ $item->ram }}"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'ram', this.value, this)">
                    </td>

                    <!-- H: Disco -->
                    <td class="celda-estado text-center">
                        <input type="text" class="input-inline text-center"
                            value="{{ $item->disco }}"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'disco', this.value, this)">
                    </td>

                    <!-- I: Video -->
                    <td class="celda-estado">
                        <input type="text" class="input-inline"
                            value="{{ $item->video }}"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'video', this.value, this)">
                    </td>

                    <!-- J: Part Number -->
                    <td class="celda-estado font-monospace">
                        <input type="text" class="input-inline font-monospace"
                            value="{{ $item->part_number }}"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'part_number', this.value, this)">
                    </td>

                    <!-- K: Stock -->
                    <td class="celda-estado text-center">
                        <input type="text" class="input-inline text-center fw-bold"
                            value="{{ $item->stock_texto }}"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'stock_texto', this.value, this)">
                    </td>

                    <!-- L: Fecha de Revisión (Rosa si < hoy) -->
                    <td id="celda-fecha-{{ $item->idLaptopSeguimiento }}"
                        class="text-center {{ $vencido ? 'celda-fecha-vencida' : '' }}">
                        <div class="d-flex align-items-center gap-1">
                            <input type="date" class="input-inline text-center p-0"
                                value="{{ $item->fecha_revision ? $item->fecha_revision->format('Y-m-d') : '' }}"
                                onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'fecha_revision', this.value, this)">
                            <button type="button" class="btn btn-xs btn-outline-success py-0 px-1"
                                style="font-size: 0.65rem;"
                                title="Marcar revisado HOY"
                                onclick="marcarRevisadoHoy({{ $item->idLaptopSeguimiento }})">
                                <i class="bi bi-check2"></i>
                            </button>
                        </div>
                    </td>

                    <!-- M, N, O, P: Proveedores CUAD (40), DM (59), ALC (48), ASIA (22) -->
                    @foreach($proveedoresCfg as $idProv => $pCfg)
                    @php
                    $celdaP = $provs[$idProv] ?? $provs[(string)$idProv] ?? ['valor' => '', 'tipo' => 'VACIO'];
                    $tipoP = $celdaP['tipo'] ?? 'VACIO';
                    $valP = $celdaP['valor'] ?? '';
                    @endphp
                    <td id="celda-prov-{{ $item->idLaptopSeguimiento }}-{{ $idProv }}"
                        class="text-center celda-prov-{{ $tipoP }}">
                        <input type="text" class="input-inline text-center"
                            value="{{ $valP }}"
                            placeholder="-"
                            title="{{ $pCfg['nombre'] }}: 'x' = Con stock, 'PROV' = En lista sin stock, 'W0...' = Código"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'prov_{{ $idProv }}', this.value, this)">
                    </td>
                    @endforeach

                    <!-- Q: unik (stock propio) -->
                    <td class="text-center">
                        <input type="text" class="input-inline text-center fw-bold"
                            value="{{ $item->stock_unik }}"
                            onchange="guardarCeldaInline({{ $item->idLaptopSeguimiento }}, 'stock_unik', this.value, this)">
                    </td>

                    <!-- R: Cuentas ML (UNIK=3, GAMING=4, UP=5, LIZ=6) -->
                    <td class="text-center">
                        <div class="d-flex flex-wrap justify-content-center gap-1">
                            @foreach($cuentasMlCfg as $idCta => $cCfg)
                            @php
                            $activa = in_array((int)$idCta, $ctasIds, true);
                            @endphp
                            <span id="pill-cta-{{ $item->idLaptopSeguimiento }}-{{ $idCta }}"
                                class="badge bg-{{ $cCfg['color'] }} cuenta-pill {{ $activa ? '' : 'inactiva' }}"
                                title="{{ $cCfg['nombre'] }} (Clic para activar/desactivar)"
                                onclick="toggleCuentaMl({{ $item->idLaptopSeguimiento }}, {{ $idCta }}, this)">
                                {{ $cCfg['sigla'] }}
                            </span>
                            @endforeach
                        </div>
                    </td>

                    <!-- Selector de Estado de Fila + Eliminar -->
                    <td class="text-center">
                        <div class="d-flex align-items-center gap-1">
                            <select class="form-select form-select-sm py-0 px-1" style="font-size: 0.70rem;"
                                onchange="cambiarEstadoFila({{ $item->idLaptopSeguimiento }}, this.value)">
                                @foreach($estadosCfg as $eKey => $eData)
                                <option value="{{ $eKey }}" @selected($item->estado_fila === $eKey)>{{ $eData['short'] }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-xs text-danger p-0" title="Eliminar fila"
                                onclick="eliminarFilaSeguimiento({{ $item->idLaptopSeguimiento }})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
                @endforeach
                @endforeach
                @empty
                <tr>
                    <td colspan="19" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No se encontraron modelos con los filtros seleccionados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Agregar Nueva Laptop al Seguimiento -->
<div class="modal fade" id="modalNuevaLaptop" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('laptops.seguimiento.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold"><i class="bi bi-laptop me-1"></i> Agregar Modelo al Tablero de Seguimiento</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Marca *</label>
                        <select name="marca_nombre" class="form-select form-select-sm" required>
                            @foreach(array_keys($marcasCfg) as $mName)
                            <option value="{{ $mName }}">{{ $mName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Modelo *</label>
                        <input type="text" name="modelo" class="form-control form-control-sm" required placeholder="Ej: ANV15-52-778V NITRO V 15">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Part Number (P/N)</label>
                        <input type="text" name="part_number" class="form-control form-control-sm font-monospace" placeholder="Ej: NH.U1PAA.003">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Serie / Línea</label>
                        <input type="text" name="serie" class="form-control form-control-sm" placeholder="Ej: NITRO, ROG STRIX">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Tamaño</label>
                        <input type="text" name="tamano" class="form-control form-control-sm" placeholder='15.6"'>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Frecuencia (Hz)</label>
                        <select name="frecuencia" class="form-select form-select-sm">
                            <option value="">-- Hz --</option>
                            @foreach($frecuenciasSugeridas as $sug)
                                <option value="{{ $sug }}">{{ $sug }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Procesador</label>
                        <input type="text" name="procesador" class="form-control form-control-sm" placeholder="Ej: i7 13620H / RYZEN 7 260HS">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">RAM</label>
                        <input type="text" name="ram" class="form-control form-control-sm" placeholder="16GB">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Disco</label>
                        <input type="text" name="disco" class="form-control form-control-sm" placeholder="512GB / 1TB">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Video (GPU)</label>
                        <input type="text" name="video" class="form-control form-control-sm" placeholder="8GB 5050">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Responsable</label>
                        <input type="text" name="responsable_texto" class="form-control form-control-sm" placeholder="ANGIE / FER">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Estado Inicial</label>
                        <select name="estado_fila" class="form-select form-select-sm">
                            @foreach($estadosCfg as $eKey => $eData)
                            <option value="{{ $eKey }}">{{ $eData['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <hr class="my-2">
                    <div class="col-12">
                        <label class="form-label small fw-bold text-primary mb-1">Proveedores (`x` = stock, `PROV` = sin stock confirmado, `W06335` = código)</label>
                        <div class="row g-2">
                            @foreach($proveedoresCfg as $idProv => $pCfg)
                            <div class="col-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">{{ $pCfg['codigo'] }}</span>
                                    <input type="text" name="prov_{{ $idProv }}" class="form-control" placeholder="x / PROV / W...">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <label class="form-label small fw-bold text-primary mb-1">Cuentas Mercado Libre Publicadas</label>
                        <div class="d-flex gap-3">
                            @foreach($cuentasMlCfg as $idCta => $cCfg)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="cuentas_ml[]" value="{{ $idCta }}" id="chkCtaNew{{ $idCta }}">
                                <label class="form-check-label small fw-bold" for="chkCtaNew{{ $idCta }}">
                                    {{ $cCfg['sigla'] }} ({{ $cCfg['nombre'] }})
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-primary fw-bold">Guardar Modelo</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Importar desde Libro11.xlsx -->
<div class="modal fade" id="modalImportarExcel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('laptops.seguimiento.importar') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-spreadsheet me-1"></i> Importar Tablero Excel (Libro11.xlsx)</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    Puedes subir un archivo <code>.xlsx</code> actualizado o dejar el campo vacío para recargar directamente desde <code>storage/Libro11.xlsx</code>.
                </p>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Archivo Excel (opcional)</label>
                    <input type="file" name="archivo_excel" accept=".xlsx,.xls" class="form-control form-control-sm">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-sm btn-success fw-bold">Importar y Sincronizar</button>
            </div>
        </form>
    </div>
</div>

<!-- Datalist de Sugerencias de Frecuencia (Hz) desde Caracteristicas_Sugerencias -->
<datalist id="lista-frecuencias">
    @foreach($frecuenciasSugeridas ?? [] as $sug)
        <option value="{{ $sug }}"></option>
    @endforeach
</datalist>

@include('laptops.logic.seguimientologic')
@endsection