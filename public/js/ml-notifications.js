let mlNotifOpen = false;

function toggleMlNotifications() {
    const dropdown = document.getElementById('mlNotifDropdown');
    mlNotifOpen = !mlNotifOpen;
    dropdown.style.display = mlNotifOpen ? 'block' : 'none';
    if (mlNotifOpen) fetchMlNotifications();
}

// Cerrar al hacer clic fuera
document.addEventListener('click', function (e) {
    const wrapper = document.getElementById('mlBellWrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('mlNotifDropdown').style.display = 'none';
        mlNotifOpen = false;
    }
});

function fetchMlNotifications() {
    fetch(window.mlNotificationsUrl)
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('mlBadgeCount');
            const totalBadge = document.getElementById('mlNotifTotalBadge');
            const content = document.getElementById('mlNotifContent');

            // Actualizar badge
            if (data.total > 0) {
                badge.textContent = data.total;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
            totalBadge.textContent = data.total;

            // Renderizar contenido
            let html = '';

            // Preguntas
            if (data.questions && data.questions.length > 0) {
                html += '<div class="px-2 py-1 bg-light border-bottom"><small class="fw-bold text-muted"><i class="bi bi-chat-dots-fill text-primary me-1"></i>PREGUNTAS SIN RESPONDER (' + data.questions_count + ')</small></div>';
                data.questions.forEach(q => {
                    const date = new Date(q.date_created).toLocaleDateString('es-PE', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
                    html += `
                    <div class="border-bottom p-2" id="ml-q-${q.id}">
                        <div class="d-flex align-items-start gap-2">
                            ${q.item_thumbnail ? `<img src="${q.item_thumbnail}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;" alt="">` : ''}
                            <div class="flex-grow-1">
                                <div class="fw-bold small text-truncate" style="max-width:280px;" title="${q.item_title}">${q.item_title}</div>
                                <div class="small text-dark mt-1">"${q.text}"</div>
                                <div class="text-muted" style="font-size:10px;">${date}</div>
                                <div class="mt-2">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="ml-answer-${q.id}" placeholder="Escribe tu respuesta...">
                                        <button class="btn btn-sm btn-primary" onclick="answerMlQuestion(${q.id})">
                                            <i class="bi bi-send-fill"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
            }

            // Reclamos
            if (data.claims && data.claims.length > 0) {
                html += '<div class="px-2 py-1 bg-light border-bottom mt-1"><small class="fw-bold text-muted"><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>RECLAMOS ABIERTOS (' + data.claims_count + ')</small></div>';
                data.claims.forEach(c => {
                    const reason = c.reason_id || c.type || 'Reclamo';
                    html += `
                    <div class="border-bottom p-2">
                        <div class="small">
                            <span class="badge bg-danger me-1">Reclamo</span>
                            <span class="fw-bold">${reason}</span>
                        </div>
                        <div class="text-muted" style="font-size:10px;">ID: ${c.id || '—'}</div>
                    </div>`;
                });
            }

            if (!html) {
                html = '<div class="text-center text-muted py-4"><i class="bi bi-check-circle fs-3 d-block mb-2 text-success"></i>No hay notificaciones pendientes</div>';
            }

            content.innerHTML = html;
        })
        .catch(err => {
            document.getElementById('mlNotifContent').innerHTML = '<div class="text-center text-danger py-3"><i class="bi bi-exclamation-triangle"></i> Error al cargar</div>';
        });
}

function answerMlQuestion(questionId) {
    const input = document.getElementById('ml-answer-' + questionId);
    const text = input.value.trim();
    if (!text) return;

    const btn = input.nextElementSibling;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(window.mlAnswerUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ question_id: questionId, text: text })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const qDiv = document.getElementById('ml-q-' + questionId);
                qDiv.innerHTML = '<div class="text-success small p-2"><i class="bi bi-check-circle-fill"></i> Respondido correctamente</div>';
                setTimeout(() => { qDiv.remove(); fetchMlNotifications(); }, 1500);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill"></i>';
                alert('Error: ' + (data.error || 'No se pudo responder'));
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i>';
        });
}

// Polling: consultar cada 5 minutos
function pollMlBadge() {
    fetch(window.mlNotificationsUrl)
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('mlBadgeCount');
            if (data.total > 0) {
                badge.textContent = data.total;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(() => { });
}

// Cargar al inicio y repetir cada 5 min
pollMlBadge();
setInterval(pollMlBadge, 300000);
