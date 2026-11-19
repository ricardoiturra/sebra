import { createSpeechInput, createSpeechOutput, inputSupported, outputSupported } from './voice-providers.js';

export function connectVoice(form, root) {
    const config = JSON.parse(root.dataset.voiceConfig);
    config.csrf = document.querySelector('meta[name="csrf-token"]').content;
    const input = createSpeechInput(config);
    const output = createSpeechOutput(config);
    const record = document.querySelector('#voice-record');
    const cancel = root.querySelector('#voice-cancel');
    const consent = root.querySelector('#voice-consent');
    const autoSend = root.querySelector('#voice-auto-send');
    const autoRead = root.querySelector('#voice-auto-read');
    const play = root.querySelector('#voice-play');
    const pause = root.querySelector('#voice-pause');
    const stop = root.querySelector('#voice-stop');
    const status = root.querySelector('#voice-status');
    let recording = false;
    let busy = false;
    let speech = form.queryResult?.speech_text || '';
    let playback = 'ready';
    const messages = {
        ready: 'Voz lista. Podés corregir la transcripción antes de consultar.', recording: 'Grabando… Pulsá detener cuando termines.',
        transcribing: 'Transcribiendo…', processing: 'Procesando la consulta…', synthesizing: 'Preparando la lectura…',
        playing: 'Reproduciendo voz sintética.', paused: 'Lectura en pausa.', error: 'Error de audio. Podés seguir escribiendo.',
    };
    const canRecord = inputSupported(config);
    const canSpeak = outputSupported(config);
    function update() {
        record.disabled = !canRecord || !consent.checked || busy || root.dataset.state === 'transcribing';
        record.textContent = recording ? '■ Detener' : '🎙 Micrófono';
        record.setAttribute('aria-pressed', String(recording));
        record.classList.toggle('border-red-500', recording);
        record.classList.toggle('border-cyan-700', !recording);
        record.setAttribute('aria-label', recording ? 'Detener grabación' : 'Iniciar grabación');
        play.disabled = !canSpeak || !consent.checked || !speech || busy || recording || root.dataset.state === 'transcribing' || ['playing', 'synthesizing'].includes(playback);
        pause.disabled = playback !== 'playing';
        stop.disabled = !['playing', 'paused', 'synthesizing'].includes(playback);
        cancel.hidden = !recording && root.dataset.state !== 'transcribing';
    }
    function state(name, message) {
        if (name === 'transcribing') recording = false;
        root.dataset.state = name;
        root.setAttribute('aria-busy', String(['transcribing', 'processing', 'synthesizing'].includes(name)));
        status.textContent = message || messages[name];
        status.classList.toggle('text-red-300', name === 'error');
        status.classList.toggle('text-cyan-200', name !== 'error');
        update();
    }
    function error(message) {
        recording = false;
        busy = false;
        playback = 'ready';
        state('error', message);
    }
    const playbackCallbacks = { state: (name) => { playback = name; state(name); }, error };
    async function read() {
        if (!consent.checked || !speech || busy) return;
        await output.play(speech, playbackCallbacks);
    }
    record.addEventListener('click', () => {
        if (recording) { recording = false; input.stop(); update(); return; }
        if (!consent.checked || busy) return;
        output.stop();
        playback = 'ready';
        recording = true;
        input.start({ state, error, transcript: (text) => {
            recording = false;
            form.elements.question.value = text;
            form.elements.question.dispatchEvent(new Event('input', { bubbles: true }));
            state('ready', 'Transcripción lista. Corregila si hace falta y pulsá consultar.');
            form.elements.question.focus();
            if (autoSend.checked && consent.checked) form.requestSubmit();
        } });
    });
    cancel.addEventListener('click', () => { input.cancel(); recording = false; state('ready', 'Grabación cancelada. No se enviará la transcripción.'); });
    consent.addEventListener('change', () => {
        if (!consent.checked) { input.cancel(); output.stop(); recording = false; playback = 'ready'; state('ready', 'Voz desactivada. Podés seguir escribiendo.'); }
        update();
    });
    play.addEventListener('click', read);
    pause.addEventListener('click', () => { output.pause(); playback = 'paused'; state('paused'); });
    stop.addEventListener('click', () => { output.stop(); playback = 'ready'; state('ready', 'Lectura detenida.'); });
    autoRead.addEventListener('change', () => { if (!autoRead.checked) { output.stop(); playback = 'ready'; update(); } });
    form.addEventListener('sebra:query-start', () => {
        input.cancel(); output.stop(); recording = false; busy = true; playback = 'ready'; speech = ''; state('processing');
    });
    form.addEventListener('sebra:query-answer', (event) => {
        busy = false;
        // No client-side reconstruction of references: the backend prepares the oral text.
        speech = event.detail.speech_text || '';
        state('ready');
        if (autoRead.checked && consent.checked && speech) read();
    });
    form.addEventListener('sebra:query-error', () => { busy = false; state('error', 'La consulta falló. Podés corregir el texto y volver a intentar.'); });
    window.addEventListener('pagehide', () => { input.cancel(); output.stop(); });
    state('ready', canRecord ? 'La voz es opcional. Activá el consentimiento para usarla.' : 'El micrófono no está disponible en este navegador o proveedor. Podés usar el teclado.');
    autoRead.disabled = !canSpeak;
    autoSend.disabled = !canRecord;
}
