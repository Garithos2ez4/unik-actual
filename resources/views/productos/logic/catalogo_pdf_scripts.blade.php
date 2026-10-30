    @php
    $tokensPdf = [
    'categoria' => [],
    'grupo' => []
    ];

    // 1. Tokens para toda la categoria (idGrupo = null)
    $tokensPdf['categoria'][''] = [
    'ver' => route('catalogo.precios.pdf.ver', ['token' => encrypt([
    'idCategoria' => (int) $categoria['idCategoria'],
    'idGrupo' => null,
    'idMarca' => null,
    'exp' => now()->addHours(24)->timestamp
    ])]),
    'descargar' => route('catalogo.precios.pdf.descargar', ['token' => encrypt([
    'idCategoria' => (int) $categoria['idCategoria'],
    'idGrupo' => null,
    'idMarca' => null,
    'exp' => now()->addHours(24)->timestamp
    ])])
    ];

    // 2. Tokens para solo este grupo (idGrupo = actual)
    $tokensPdf['grupo'][''] = [
    'ver' => route('catalogo.precios.pdf.ver', ['token' => encrypt([
    'idCategoria' => (int) $categoria['idCategoria'],
    'idGrupo' => (int) $grupo['idGrupoProducto'],
    'idMarca' => null,
    'exp' => now()->addHours(24)->timestamp
    ])]),
    'descargar' => route('catalogo.precios.pdf.descargar', ['token' => encrypt([
    'idCategoria' => (int) $categoria['idCategoria'],
    'idGrupo' => (int) $grupo['idGrupoProducto'],
    'idMarca' => null,
    'exp' => now()->addHours(24)->timestamp
    ])])
    ];

    foreach ($filtros['marcas'] as $m) {
    $idM = data_get($m, 'idMarca') ?? data_get($m, 'id');
    if ($idM) {
    // Categoria + Marca (Grupo opcional / null)
    $tokensPdf['categoria'][(string)$idM] = [
    'ver' => route('catalogo.precios.pdf.ver', ['token' => encrypt([
    'idCategoria' => (int) $categoria['idCategoria'],
    'idGrupo' => null,
    'idMarca' => (int) $idM,
    'exp' => now()->addHours(24)->timestamp
    ])]),
    'descargar' => route('catalogo.precios.pdf.descargar', ['token' => encrypt([
    'idCategoria' => (int) $categoria['idCategoria'],
    'idGrupo' => null,
    'idMarca' => (int) $idM,
    'exp' => now()->addHours(24)->timestamp
    ])])
    ];

    // Solo este Grupo + Marca
    $tokensPdf['grupo'][(string)$idM] = [
    'ver' => route('catalogo.precios.pdf.ver', ['token' => encrypt([
    'idCategoria' => (int) $categoria['idCategoria'],
    'idGrupo' => (int) $grupo['idGrupoProducto'],
    'idMarca' => (int) $idM,
    'exp' => now()->addHours(24)->timestamp
    ])]),
    'descargar' => route('catalogo.precios.pdf.descargar', ['token' => encrypt([
    'idCategoria' => (int) $categoria['idCategoria'],
    'idGrupo' => (int) $grupo['idGrupoProducto'],
    'idMarca' => (int) $idM,
    'exp' => now()->addHours(24)->timestamp
    ])])
    ];
    }
    }
    @endphp

    <script>
        window.tokensPdf = @json($tokensPdf);

        function obtenerUrlPdfActual(tipo) {
            const selectMarca = document.getElementById('select-filtro-marca') || document.querySelector('select[name="filtro[marca]"]');
            const idMarca = selectMarca && selectMarca.value ? String(selectMarca.value) : '';
            const checkGrupo = document.getElementById('check-solo-grupo');
            const modo = (checkGrupo && checkGrupo.checked) ? 'grupo' : 'categoria';

            const scopeTokens = window.tokensPdf && window.tokensPdf[modo] ? window.tokensPdf[modo] : null;
            if (scopeTokens && scopeTokens[idMarca]) {
                return scopeTokens[idMarca][tipo];
            }
            return scopeTokens && scopeTokens[''] ? scopeTokens[''][tipo] : '';
        }

        function sincronizarEnlacesPdf() {
            const selectMarca = document.getElementById('select-filtro-marca') || document.querySelector('select[name="filtro[marca]"]');
            const idMarca = selectMarca && selectMarca.value ? String(selectMarca.value) : '';
            const checkGrupo = document.getElementById('check-solo-grupo');
            const modo = (checkGrupo && checkGrupo.checked) ? 'grupo' : 'categoria';

            const scopeTokens = window.tokensPdf && window.tokensPdf[modo] ? window.tokensPdf[modo] : null;
            const item = (scopeTokens && scopeTokens[idMarca]) ? scopeTokens[idMarca] : (scopeTokens ? scopeTokens[''] : null);

            if (item) {
                const btnVer = document.getElementById('btn-ver-pdf');
                const btnDescargar = document.getElementById('btn-descargar-pdf');
                if (btnVer) btnVer.href = item.ver;
                if (btnDescargar) btnDescargar.href = item.descargar;
                window.currentPdfPublicUrl = item.ver;
            }
        }

        function abrirCatalogoPdf(event, tipo) {
            if (event) {
                event.preventDefault();
            }
            const url = obtenerUrlPdfActual(tipo);
            if (!url) return;

            if (tipo === 'ver') {
                window.open(url, '_blank');
            } else {
                window.location.href = url;
            }
        }

        function copiarLinkPublicoPdf(event) {
            if (event) {
                event.preventDefault();
            }
            const url = obtenerUrlPdfActual('ver');
            if (!url) return;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(function() {
                    mostrarAlertaCopiado();
                }).catch(function() {
                    fallbackCopiarTexto(url);
                });
            } else {
                fallbackCopiarTexto(url);
            }
        }

        function fallbackCopiarTexto(texto) {
            const tempInput = document.createElement('input');
            tempInput.value = texto;
            document.body.appendChild(tempInput);
            tempInput.select();
            try {
                document.execCommand('copy');
                mostrarAlertaCopiado();
            } catch (e) {
                prompt('Copia este enlace manualmente:', texto);
            }
            document.body.removeChild(tempInput);
        }

        function mostrarAlertaCopiado() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Link Copiado!',
                    text: 'El enlace público del catálogo PDF se ha copiado al portapapeles.',
                    timer: 2500,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                alert('¡Link copiado al portapapeles!');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const selectMarca = document.getElementById('select-filtro-marca') || document.querySelector('select[name="filtro[marca]"]');
            if (selectMarca) {
                selectMarca.addEventListener('change', sincronizarEnlacesPdf);
            }
            sincronizarEnlacesPdf();
        });
    </script>