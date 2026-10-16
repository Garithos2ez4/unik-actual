<script>
    function loadPreguntasDetalle() {
        const container = document.getElementById('preguntas-container');
        container.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted fw-bold">Cargando preguntas de Mercado Libre...</div></div>';

        fetch('{{ route("plataformas.ml.notifications") }}')
            .then(r => r.json())
            .then(data => {
                let html = '';
                if (data.questions && data.questions.length > 0) {
                    data.questions.forEach(q => {
                        const date = new Date(q.date_created).toLocaleDateString('es-PE', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        // Extra info
                        const priceHtml = q.item_price ? `<strong>S/ ${q.item_price}</strong>` : 'S/ N/A';
                        const stockHtml = `Stock: <strong>${q.item_available_quantity}</strong>`;
                        const linkHtml = `<a href="${q.item_permalink}" target="_blank" class="btn btn-sm btn-outline-secondary px-2 py-0"><i class="bi bi-box-arrow-up-right me-1"></i>Ver en ML</a>`;

                        let isInactive = (q.item_status && q.item_status !== "active");
                        let disabledAttr = isInactive ? "disabled" : "";
                        let placeholderText = isInactive ? "Artículo pausado/inactivo. No se puede responder." : "Escribe aquí tu respuesta detallada...";
                        let alertHtml = isInactive ? `<div class="alert alert-warning py-1 px-2 mb-2" style="font-size:0.8rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Artículo inactivo en ML. No se permite enviar respuestas.</div>` : "";

                        html += `
                        <div class="col-12 mb-3" id="q-detail-${q.id}">
                            <div class="card shadow-sm border-0 border-start border-4 border-warning">
                                <div class="card-body p-4">
                                    <div class="row">
                                        <!-- Producto -->
                                        <div class="col-md-5 border-end">
                                            <div class="d-flex gap-3">
                                                ${q.item_thumbnail ? `<img src="${q.item_thumbnail}" style="width:100px;height:100px;object-fit:contain;border-radius:8px;border:1px solid #eee;padding:4px;" alt="">` : ''}
                                                <div class="d-flex flex-column justify-content-center">
                                                    <div class="fw-bold text-dark lh-sm mb-2" style="font-size: 1rem;">${q.item_title}</div>
                                                    <div class="d-flex gap-3 text-muted" style="font-size: 0.9rem;">
                                                        <div>${priceHtml}</div>
                                                        <div>${stockHtml}</div>
                                                    </div>
                                                    <div class="mt-2">${linkHtml}</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Pregunta y Respuesta -->
                                        <div class="col-md-7 ps-md-4 mt-3 mt-md-0 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="badge bg-light text-dark border"><i class="bi bi-person-fill me-1"></i>Comprador</span>
                                                    <small class="text-muted"><i class="bi bi-clock me-1"></i>${date}</small>
                                                </div>
                                                <div class="p-3 bg-light rounded-3 mb-3 border text-dark fs-5 shadow-sm" style="font-style: italic;">
                                                    "${q.text}"
                                                </div>
                                                ${alertHtml}
                                            </div>
                                            
                                            <div class="input-group">
                                                <span class="input-group-text bg-primary text-white border-primary"><i class="bi bi-chat-left-text-fill"></i></span>
                                                <textarea class="form-control border-primary shadow-sm" id="ans-detail-${q.id}" rows="2" placeholder="${placeholderText}" ${disabledAttr}></textarea>
                                                <button class="btn btn-primary fw-bold px-4 shadow-sm" type="button" onclick="sendAnswerDetalle(${q.id}, ${q.seller_id})" ${disabledAttr}>
                                                    Enviar <i class="bi bi-send-fill ms-1"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        `;
                    });
                } else {
                    html = `
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h3 class="mt-3 text-dark fw-bold">¡Todo al día!</h3>
                        <p class="text-muted">No tienes preguntas sin responder en este momento.</p>
                    </div>`;
                }
                container.innerHTML = html;
            })
            .catch(err => {
                container.innerHTML = '<div class="col-12 text-center text-danger py-5"><i class="bi bi-exclamation-triangle fs-1"></i><div class="mt-2">Error al cargar las preguntas. Intenta de nuevo.</div></div>';
            });
    }

    function sendAnswerDetalle(questionId, sellerId) {
        const input = document.getElementById('ans-detail-' + questionId);
        const text = input.value.trim();
        if (!text) {
            input.classList.add('is-invalid');
            return;
        }
        input.classList.remove('is-invalid');

        const btn = input.nextElementSibling;
        const originalBtnHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';

        fetch('{{ route("plataformas.ml.answer") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    question_id: questionId,
                    text: text,
                    seller_id: sellerId
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const qDiv = document.getElementById('q-detail-' + questionId);
                    qDiv.innerHTML = '<div class="card border-0 bg-success text-white text-center py-4 rounded-3 shadow-sm"><i class="bi bi-check-circle-fill fs-2 mb-2"></i><div class="fw-bold fs-5">Respuesta enviada correctamente</div></div>';
                    setTimeout(() => {
                        qDiv.remove();
                    }, 2000);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                    alert('Error: ' + (data.error || 'No se pudo responder'));
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
                alert('Error de conexión al enviar la respuesta.');
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadPreguntasDetalle();
    });
</script>
