<script src="https://unpkg.com/pdf-lib/dist/pdf-lib.min.js"></script>
<script>
    // Generar etiquetas de TODAS las órdenes del día
    async function generarEtiquetasFalabella(url) {
        const btn = document.getElementById('btn-generar-etiquetas');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';

        try {
            await descargarEtiquetasPDF(url);
        } catch (err) {
            alert('Error al generar etiquetas: ' + err.message);
            console.error(err);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    // Generar etiqueta de una orden individual
    async function generarEtiquetaOrden(orderId, date, status) {
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        const url = '{{ route("plataformas.falabella.etiquetas-oficiales.pdf") }}?date=' + date + '&status=' + status + '&order_ids=' + orderId;

        try {
            await descargarEtiquetasPDF(url);
        } catch (err) {
            alert('Error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    async function descargarEtiquetasPDF(url) {
        const response = await fetch(url);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Error desconocido al obtener etiquetas');
        }

        if (!data.labels || data.labels.length === 0) {
            alert('No hay etiquetas disponibles para las órdenes seleccionadas.');
            return;
        }

        const { PDFDocument } = PDFLib;
        const outPdf = await PDFDocument.create();
        const pageWidth = 595.28;
        const pageHeight = 841.89;

        let currentPage = null;
        let labelCount = 0;

        for (const base64 of data.labels) {
            try {
                const labelPdf = await PDFDocument.load(base64);
                const pagesCount = labelPdf.getPageCount();

                // Recortamos todas las hojas originales al área de la etiqueta
                for (let i = 0; i < pagesCount; i++) {
                    const sourcePage = labelPdf.getPages()[i];
                    const spHeight = sourcePage.getHeight();
                    const cropWidth = 283.46; // 100mm
                    const cropHeight = 425.20; // 150mm
                    sourcePage.setCropBox(0, spHeight - cropHeight, cropWidth, cropHeight);
                }
                
                // FIX: Guardar y recargar para que el embedPdf respete el CropBox
                const savedBytes = await labelPdf.save();
                const croppedPdf = await PDFDocument.load(savedBytes);

                for (let i = 0; i < pagesCount; i++) {
                    const [embeddedPage] = await outPdf.embedPdf(croppedPdf, [i]);

                    if (labelCount % 4 === 0) {
                        currentPage = outPdf.addPage([pageWidth, pageHeight]);
                    }

                    const indexOnPage = labelCount % 4;
                    const col = indexOnPage % 2;
                    const row = Math.floor(indexOnPage / 2);
                    
                    const quadWidth = pageWidth / 2;
                    const quadHeight = pageHeight / 2;
                    
                    const safeScale = 0.94; // 94% scale para márgenes
                    const finalScale = Math.min(quadWidth / embeddedPage.width, quadHeight / embeddedPage.height) * safeScale;
                    
                    const drawWidth = embeddedPage.width * finalScale;
                    const drawHeight = embeddedPage.height * finalScale;

                    const offsetX = (quadWidth - drawWidth) / 2;
                    const offsetY = (quadHeight - drawHeight) / 2;

                    const x = (col * quadWidth) + offsetX;
                    const y = (pageHeight - ((row + 1) * quadHeight)) + offsetY;

                    currentPage.drawPage(embeddedPage, { x, y, width: drawWidth, height: drawHeight });
                    labelCount++;
                }
            } catch (err) {
                console.error('Error al incrustar etiqueta:', err);
            }
        }

        const pdfBytes = await outPdf.save();
        const blob = new Blob([pdfBytes], { type: 'application/pdf' });
        const blobUrl = URL.createObjectURL(blob);
        window.open(blobUrl, '_blank');
    }
</script>

