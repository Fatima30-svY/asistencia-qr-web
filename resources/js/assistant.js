async function askAssistant(endpoint, data) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 70000);
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            signal: controller.signal,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(data),
        });
        if (response.status === 429) throw new Error('Llegaste al límite de consultas. Espera un momento; si alcanzaste el límite diario, vuelve mañana.');
        if (response.status === 401 || response.status === 419) throw new Error('Tu sesión venció. Vuelve a iniciar sesión.');
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo consultar la IA.');
        return result.respuesta;
    } catch (error) {
        if (error.name === 'AbortError') throw new Error('La IA tardó demasiado. Puedes seguir trabajando e intentarlo más tarde.');
        if (error instanceof TypeError) throw new Error('No se pudo conectar. Revisa la conexión y vuelve a intentarlo.');
        throw error;
    } finally {
        clearTimeout(timer);
    }
}

const assistant = document.querySelector('[data-assistant]');
if (assistant) {
    const toggle = assistant.querySelector('[data-assistant-toggle]');
    const panel = assistant.querySelector('[data-assistant-panel]');
    const input = assistant.querySelector('[data-assistant-input]');
    const messages = assistant.querySelector('[data-assistant-messages]');
    const status = assistant.querySelector('[data-assistant-status]');
    const send = assistant.querySelector('[data-assistant-send]');
    const clear = assistant.querySelector('[data-assistant-clear]');
    const introduction = messages.firstElementChild.cloneNode(true);
    let history = [];
    let busy = false;

    function close() {
        panel.hidden = true;
        panel.style.display = 'none';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    }
    toggle.addEventListener('click', () => {
        if (!panel.hidden) return close();
        panel.hidden = false;
        panel.style.display = 'flex';
        toggle.setAttribute('aria-expanded', 'true');
        input.focus();
    });
    assistant.querySelector('[data-assistant-close]').addEventListener('click', close);
    assistant.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
    assistant.querySelector('[data-assistant-example]').addEventListener('click', event => {
        input.value = event.target.textContent.trim();
        input.focus();
    });
    clear.addEventListener('click', () => {
        if (busy) return;
        history = [];
        messages.replaceChildren(introduction.cloneNode(true));
        status.textContent = '';
    });

    function bubble(text, own) {
        const paragraph = document.createElement('p');
        paragraph.className = own ? 'rounded-lg bg-blue-50 p-3 text-sm text-blue-900 whitespace-pre-wrap' : 'rounded-lg bg-slate-100 p-3 text-sm text-slate-700 whitespace-pre-wrap';
        paragraph.textContent = text;
        messages.append(paragraph);
        while (messages.children.length > 15) messages.firstElementChild.remove();
        messages.scrollTop = messages.scrollHeight;
    }
    assistant.querySelector('[data-assistant-form]').addEventListener('submit', async event => {
        event.preventDefault();
        const question = input.value.trim();
        if (busy || !question) return;
        busy = true;
        send.disabled = true;
        clear.disabled = true;
        input.disabled = true;
        status.textContent = 'Consultando la IA local…';
        bubble(question, true);
        try {
            const answer = await askAssistant(assistant.dataset.endpoint, { pregunta: question, historial: history });
            bubble(answer, false);
            history = [...history, { role: 'user', content: question }, { role: 'assistant', content: answer.slice(0, 600) }].slice(-4);
            input.value = '';
            status.textContent = 'Respuesta orientativa. Verifica la información importante.';
        } catch (error) {
            status.textContent = error.message;
        } finally {
            busy = false;
            send.disabled = false;
            clear.disabled = false;
            input.disabled = false;
        }
    });
}

document.querySelectorAll('[data-ai-description]').forEach(container => {
    const form = container.closest('form');
    const title = form.elements.namedItem('nombre');
    const semester = form.elements.namedItem('semestre');
    const description = form.elements.namedItem('descripcion');
    const button = container.querySelector('[data-ai-suggest]');
    const preview = container.querySelector('[data-ai-preview]');
    const status = container.querySelector('[data-ai-description-status]');
    let suggestion = '';
    let source = '';
    function fingerprint() { return JSON.stringify([title.value.trim(), semester.value]); }

    button.addEventListener('click', async () => {
        if (button.disabled) return;
        const name = title.value.trim();
        if (name.length < 3 || name.length > 120) {
            status.textContent = 'Escribe primero un título de entre 3 y 120 caracteres.';
            return;
        }
        source = fingerprint();
        button.disabled = true;
        preview.hidden = true;
        status.textContent = 'Preparando una propuesta breve…';
        try {
            const answer = await askAssistant(container.dataset.endpoint, { nombre: name, semestre: semester.value || null });
            if (source !== fingerprint()) {
                status.textContent = 'Cambiaste el título o semestre. Solicita una nueva propuesta.';
                return;
            }
            suggestion = answer;
            container.querySelector('[data-ai-description-text]').textContent = answer;
            preview.hidden = false;
            status.textContent = 'Revisa la propuesta antes de usarla. No representa un programa oficial.';
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    });
    container.querySelector('[data-ai-apply]').addEventListener('click', () => {
        if (source !== fingerprint()) {
            preview.hidden = true;
            status.textContent = 'El título o semestre cambió. Solicita una nueva propuesta.';
            return;
        }
        if (description.value.trim() && !window.confirm('¿Reemplazar la descripción actual por esta sugerencia?')) return;
        description.value = suggestion;
        description.dispatchEvent(new Event('input', { bubbles: true }));
        preview.hidden = true;
        status.textContent = 'Sugerencia aplicada al formulario. Puedes editarla; pulsa Guardar cambios cuando esté lista.';
    });
    container.querySelector('[data-ai-discard]').addEventListener('click', () => {
        preview.hidden = true;
        suggestion = '';
        status.textContent = 'Propuesta descartada. Tu descripción se conserva.';
    });
});
