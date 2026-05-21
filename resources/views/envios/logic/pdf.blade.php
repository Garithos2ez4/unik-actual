<script>
    const REMITENTES = {
        1: {
            nombre:  'FLOR DE MARIA QUIÑONES ENRIQUEZ',
            dir:     'AV. RAMON CARCAMO 785 - E203',
            ciudad:  'LIMA - LIMA',
            cel:     '949064561',
            doc:     '10424505787'
        },
        2: {
            nombre:  'UNIK TECHNOLOGY S.A.C.',
            dir:     'AV. BOLIVIA 180 SS-127',
            ciudad:  'LIMA - LIMA',
            cel:     '959062011',
            doc:     '20606545470'
        }
    };

    function cambiarRemitente(id) {
        const r = REMITENTES[id];
        if (!r) return;

        document.querySelectorAll('.rem-nombre').forEach(el => el.textContent = r.nombre);
        document.querySelectorAll('.rem-dir').forEach(el    => el.textContent = 'Dir.: ' + r.dir);
        document.querySelectorAll('.rem-ciudad').forEach(el => el.textContent = 'Ciu.: ' + r.ciudad);
        document.querySelectorAll('.rem-cel').forEach(el    => el.textContent = 'Cel.: ' + r.cel);
        document.querySelectorAll('.rem-doc').forEach(el    => el.textContent = 'DNI/RUC: ' + r.doc);
    }

    let originalWrappers = [];

    document.addEventListener("DOMContentLoaded", function() {
        originalWrappers = Array.from(document.querySelectorAll('.rotulo-wrapper'));

        const total = originalWrappers.length;
        let layout = 1;

        if (total === 2) {
            layout = 2;
        } else if (total === 3 || total === 4) {
            layout = 4;
        } else if (total >= 5) {
            layout = 6;
        }

        changeLayout(layout);

        // Aplicar el remitente por defecto (Remitente 1)
        cambiarRemitente(1);
    });

    function changeLayout(num) {
        const container = document.getElementById('rotulos-container');
        const buttons   = document.querySelectorAll('.btn-layout');

        // Limpiamos el contenedor
        container.innerHTML = '';

        // Agrupamos los rótulos en páginas según la cantidad por hoja (num)
        let currentPage;
        originalWrappers.forEach((wrapper, index) => {
            if (index % num === 0) {
                currentPage = document.createElement('div');
                currentPage.className = `print-page layout-${num}`;
                container.appendChild(currentPage);
            }
            currentPage.appendChild(wrapper);
        });

        // Actualizar botones activos
        buttons.forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.getElementById(`btn-l${num}`);
        if (activeBtn) activeBtn.classList.add('active');
    }
</script>
