const display = document.querySelector('[data-qr-display]');

if (display) {
    const status = document.querySelector('[data-qr-status]');
    let stopped = display.dataset.finalizada === '1';
    let timer;

    display.addEventListener('click', async event => {
        if (!event.target.closest('[data-copy-code]')) return;
        const input = display.querySelector('[data-manual-code]');
        if (!input) return;
        try {
            await navigator.clipboard.writeText(input.value);
            status.textContent = 'Código copiado. Úsalo en la cuenta del alumno.';
        } catch {
            input.select();
            status.textContent = 'Código seleccionado. Usa Ctrl+C para copiarlo.';
        }
    });

    async function refresh() {
        if (stopped) return;
        try {
            if (!document.hidden) {
                const response = await fetch(display.dataset.url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                if (!response.ok) throw new Error();
                const result = await response.json();
                if (display.innerHTML !== result.html) display.innerHTML = result.html;
                stopped = result.finalizada;
                status.textContent = stopped ? 'La clase ya no admite registros.' : 'Código vigente. Comprobación automática cada 15 segundos.';
            }
        } catch {
            status.textContent = 'No se pudo comprobar la vigencia. Revisa la conexión o actualiza la pantalla.';
        }
        if (!stopped) timer = setTimeout(refresh, 15000);
    }

    timer = setTimeout(refresh, 15000);
    window.addEventListener('pagehide', () => { stopped = true; clearTimeout(timer); });
}
