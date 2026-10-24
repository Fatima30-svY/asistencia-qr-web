const scanner = document.querySelector('[data-scanner]');

if (scanner) {
    const video = scanner.querySelector('[data-video]');
    const status = scanner.querySelector('[data-status]');
    const input = scanner.querySelector('[data-qr]');
    let stream;
    let running = false;
    let busy = false;

    function stop() {
        running = false;
        stream?.getTracks().forEach(track => track.stop());
        video.hidden = true;
    }

    async function register(raw) {
        if (busy) return;
        busy = true;
        try {
            let data;
            if (/^\d+-[a-fA-F0-9]{10}$/.test(raw.trim())) {
                data = { codigo: raw.trim().toUpperCase() };
            } else {
              try {
                const url = new URL(raw.trim());
                const match = url.pathname.match(/\/alumno\/asistencia\/(\d+)\/?$/);
                if (!match || !url.searchParams.get('token')) throw new Error();
                data = { clase_id: match[1], token: url.searchParams.get('token') };
            } catch {
                const payload = JSON.parse(raw);
                data = { clase_id: payload.c, token: payload.t };
              }
            }
            const response = await fetch(scanner.dataset.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(data),
            });
            const result = await response.json();
            status.textContent = result.message || 'No se pudo registrar la asistencia.';
            if (response.ok) {
                stop();
                window.location.assign('/alumno');
            }
        } catch {
            status.textContent = 'No se pudo procesar. Revisa el QR, tu sesión y la conexión e inténtalo de nuevo.';
        } finally {
            busy = false;
        }
    }

    scanner.querySelector('[data-scan-form]').addEventListener('submit', event => {
        event.preventDefault();
        register(input.value);
    });
    scanner.querySelector('[data-stop]').addEventListener('click', stop);
    window.addEventListener('pagehide', stop);
    scanner.querySelector('[data-camera]').addEventListener('click', async () => {
        stop();
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia || !('BarcodeDetector' in window)) {
            status.textContent = 'El escáner integrado no está disponible. Usa la cámara del teléfono para abrir el QR o pega su enlace. La cámara requiere HTTPS o localhost.';
            return;
        }
        try {
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream;
            video.hidden = false;
            await video.play();
            running = true;
            status.textContent = 'Apunta al QR de la clase.';
            while (running) {
                const codes = await detector.detect(video);
                if (codes.length) {
                    input.value = codes[0].rawValue;
                    stop();
                    await register(input.value);
                    break;
                }
                await new Promise(resolve => setTimeout(resolve, 250));
            }
        } catch {
            stop();
            status.textContent = 'No se pudo usar la cámara. Puedes pegar el enlace del QR o abrirlo desde la cámara del teléfono.';
        }
    });
}
