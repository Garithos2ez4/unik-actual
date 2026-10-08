<script>
    async function generarEtiquetasFalabella(url) {
        const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
        if(selected.length > 0) {
            const urlObj = new URL(url.startsWith('http') ? url : window.location.origin + url);
            urlObj.searchParams.set('order_ids', selected.join(','));
            url = urlObj.toString();
        }

        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';

        try {
            const response = await fetch(url);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Error desconocido al obtener etiquetas');
            }

            if (!data.labels || data.labels.length === 0) {
                alert('No hay etiquetas disponibles para las órdenes seleccionadas.');
                return;
            }

            const {
                PDFDocument,
                degrees
            } = PDFLib;
            const outPdf = await PDFDocument.create();

            const formatSelect = document.getElementById('formatoEtiqueta');
            const formato = formatSelect ? formatSelect.value : 'a4_100';
            
            const zoomInput = document.getElementById('zoomEtiqueta');
            const userZoom = zoomInput ? parseFloat(zoomInput.value) : 1;

            let currentPage = null;
            let labelCount = 0;

            for (const base64 of data.labels) {
                try {
                    const labelPdf = await PDFDocument.load(base64);
                    const pagesCount = labelPdf.getPageCount();

                    if (formato === 'a4_1') {
                        const copiedPages = await outPdf.copyPages(labelPdf, Array.from({length: pagesCount}, (_, i) => i));
                        for (const copiedPage of copiedPages) {
                            outPdf.addPage(copiedPage);
                            labelCount++;
                        }
                        continue;
                    }

                    // Crop all pages first
                    for (let i = 0; i < pagesCount; i++) {
                        const sourcePage = labelPdf.getPages()[i];
                        const spHeight = sourcePage.getHeight();
                        const cropWidth = 283.46; // 100mm
                        const cropHeight = 425.20; // 150mm
                        sourcePage.setCropBox(0, spHeight - cropHeight, cropWidth, cropHeight);
                    }
                    
                    // Save and reload to apply cropBox for pdf-lib embed
                    const savedBytes = await labelPdf.save();
                    const croppedPdf = await PDFDocument.load(savedBytes);

                    for (let i = 0; i < pagesCount; i++) {
                        const [embeddedPage] = await outPdf.embedPdf(croppedPdf, [i]);

                        if (formato === 'termica') {
                            const pageWidth = 283.46;
                            const pageHeight = 425.20;

                            currentPage = outPdf.addPage([pageWidth, pageHeight]);

                            const scaleFactor = Math.min(pageWidth / embeddedPage.width, pageHeight / embeddedPage.height) * userZoom;
                            const drawW = embeddedPage.width * scaleFactor;
                            const drawH = embeddedPage.height * scaleFactor;

                            currentPage.drawPage(embeddedPage, {
                                x: 10,
                                y: pageHeight - drawH - 10,
                                width: drawW,
                                height: drawH
                            });
                        } else {
                            const pageWidth = 595.28;
                            const pageHeight = 841.89;

                            if (labelCount % 4 === 0) {
                                currentPage = outPdf.addPage([pageWidth, pageHeight]);
                            }

                            const indexOnPage = labelCount % 4;
                            const col = indexOnPage % 2;
                            const row = Math.floor(indexOnPage / 2);

                            const quadWidth = pageWidth / 2;
                            const quadHeight = pageHeight / 2;

                            const baseScale = Math.min(quadWidth / embeddedPage.width, quadHeight / embeddedPage.height);
                            const safeScale = 0.94 * userZoom; 
                            const finalScale = baseScale * safeScale;

                            const drawWidth = embeddedPage.width * finalScale;
                            const drawHeight = embeddedPage.height * finalScale;

                            const margin = 15;
                            const x = (col * quadWidth) + margin;
                            const y = (pageHeight - (row * quadHeight)) - drawHeight - margin;

                            currentPage.drawPage(embeddedPage, {
                                x,
                                y,
                                width: drawWidth,
                                height: drawHeight
                            });
                        }

                        labelCount++;
                    }
                } catch (err) {
                    console.error('Error al incrustar etiqueta:', err);
                }
            }

            const pdfBytes = await outPdf.save();
            const blob = new Blob([pdfBytes], {
                type: 'application/pdf'
            });
            const blobUrl = URL.createObjectURL(blob);
            window.open(blobUrl, '_blank');

        } catch (err) {
            alert('Error al generar etiquetas: ' + err.message);
            console.error(err);
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
</script>
