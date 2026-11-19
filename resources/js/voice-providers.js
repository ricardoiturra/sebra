const recordingTypes = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];

async function checkedResponse(response) {
    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        throw new Error(error.message || 'El servicio de audio no está disponible. Podés usar el teclado.');
    }
    return response;
}

export function inputSupported(config, env = window) {
    if (!env.isSecureContext) return false;
    if (config.stt === 'browser') return Boolean(env.SpeechRecognition || env.webkitSpeechRecognition);
    return config.stt === 'compatible' && env.isSecureContext && Boolean(env.navigator.mediaDevices?.getUserMedia)
        && Boolean(env.MediaRecorder?.isTypeSupported) && recordingTypes.some((type) => env.MediaRecorder.isTypeSupported(type));
}

export function outputSupported(config, env = window) {
    return config.tts === 'browser' ? Boolean(env.speechSynthesis && env.SpeechSynthesisUtterance)
        : config.tts === 'compatible' && Boolean(env.Audio);
}

export function createSpeechInput(config, env = window) {
    let current;
    function release(session) {
        env.clearTimeout(session.timer);
        session.stream?.getTracks().forEach((track) => track.stop());
    }
    function cancel() {
        const session = current;
        current = null;
        if (!session) return;
        release(session);
        session.abort?.abort();
        try { session.recognition?.abort(); } catch { /* Already stopped by the browser. */ }
        if (session.recorder?.state === 'recording') session.recorder.stop();
        session.chunks = [];
    }
    function stop() {
        if (!current) return;
        current.callbacks.state('transcribing');
        env.clearTimeout(current.timer);
        if (current.recognition) {
            try { current.recognition.stop(); } catch { current.recognition.onend?.(); }
        }
        if (current.recorder?.state === 'recording') {
            current.recorder.stop();
            current.stream.getTracks().forEach((track) => track.stop());
        }
    }
    async function start(callbacks) {
        cancel();
        const session = { callbacks, chunks: [], text: '', size: 0 };
        current = session;
        const fail = (error) => {
            if (current !== session) return;
            cancel();
            callbacks.error(error.message || 'No se pudo reconocer la voz. Podés usar el teclado.');
        };
        const complete = (text) => {
            if (current !== session) return;
            if (!text?.trim()) return fail(new Error('No se reconoció voz. Intentá de nuevo.'));
            if (text.length > 500) return fail(new Error('La transcripción excede 500 caracteres. Grabá una pregunta más corta.'));
            release(session);
            current = null;
            callbacks.transcript(text.trim());
        };
        try {
            if (!inputSupported(config, env)) throw new Error('Este navegador o proveedor no permite grabar voz. Podés usar el teclado.');
            callbacks.state('recording');
            if (config.stt === 'browser') {
                const Recognition = env.SpeechRecognition || env.webkitSpeechRecognition;
                const recognition = new Recognition();
                session.recognition = recognition;
                recognition.lang = config.locale;
                recognition.continuous = false;
                recognition.interimResults = false;
                recognition.onresult = (event) => {
                    if (current !== session) return;
                    session.text = Array.from(event.results).filter((result) => result.isFinal).map((result) => result[0].transcript).join(' ');
                };
                recognition.onerror = () => fail(new Error('No se pudo transcribir: revisá el permiso del micrófono o usá el teclado.'));
                recognition.onend = () => complete(session.text);
                recognition.start();
            } else {
                const stream = await env.navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                session.stream = stream;
                if (current !== session) { release(session); return; }
                const mimeType = recordingTypes.find((type) => env.MediaRecorder.isTypeSupported(type));
                const recorder = new env.MediaRecorder(stream, { mimeType, audioBitsPerSecond: 64000 });
                session.recorder = recorder;
                recorder.ondataavailable = (event) => {
                    if (current !== session || !event.data.size) return;
                    session.size += event.data.size;
                    if (session.size > config.maxBytes) return fail(new Error('La grabación excede el tamaño permitido.'));
                    session.chunks.push(event.data);
                };
                recorder.onerror = () => fail(new Error('Falló la grabación. Podés volver a intentar.'));
                recorder.onstop = async () => {
                    release(session);
                    if (current !== session) return;
                    callbacks.state('transcribing');
                    const blob = new Blob(session.chunks, { type: mimeType });
                    session.chunks = [];
                    if (!blob.size) return fail(new Error('La grabación está vacía.'));
                    const data = new FormData();
                    const extension = mimeType.includes('mp4') ? 'mp4' : mimeType.includes('ogg') ? 'ogg' : 'webm';
                    data.append('audio', blob, `recording.${extension}`);
                    data.append('consent', '1');
                    session.abort = new AbortController();
                    try {
                        const response = await checkedResponse(await env.fetch(config.transcribeUrl, {
                            method: 'POST', body: data, signal: session.abort.signal,
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf },
                        }));
                        complete((await response.json()).text);
                    } catch (error) { fail(error); }
                };
                recorder.start(250);
            }
            if (current === session) session.timer = env.setTimeout(stop, config.maxSeconds * 1000);
        } catch (error) { fail(error); }
    }
    return { start, stop, cancel };
}

export function createSpeechOutput(config, env = window) {
    let current;
    function stop() {
        const session = current;
        current = null;
        if (!session) return;
        session.abort?.abort();
        if (session.utterance) env.speechSynthesis.cancel();
        if (session.audio) {
            session.audio.pause();
            session.audio.removeAttribute('src');
            session.audio.load();
        }
        if (session.url) env.URL.revokeObjectURL(session.url);
    }
    async function play(text, callbacks) {
        if (current?.paused) {
            const session = current;
            session.paused = false;
            try {
                if (session.utterance) env.speechSynthesis.resume();
                else await session.audio.play();
                if (current === session) callbacks.state('playing');
            } catch {
                stop();
                callbacks.error('No se pudo reanudar el audio. Intentá reproducir de nuevo.');
            }
            return;
        }
        stop();
        const session = {};
        current = session;
        const fail = (error) => {
            if (current !== session) return;
            stop();
            callbacks.error(error.message || 'No se pudo reproducir el audio. La respuesta escrita sigue disponible.');
        };
        const ended = () => {
            if (current !== session) return;
            stop();
            callbacks.state('ready');
        };
        try {
            if (!outputSupported(config, env)) throw new Error('La lectura de voz no está disponible. La respuesta escrita sigue disponible.');
            if (!text?.trim()) throw new Error('No hay respuesta oral para reproducir.');
            callbacks.state('synthesizing');
            if (config.tts === 'browser') {
                const utterance = new env.SpeechSynthesisUtterance(text);
                session.utterance = utterance;
                utterance.lang = config.locale;
                const voices = env.speechSynthesis.getVoices();
                utterance.voice = voices.find((voice) => voice.lang === config.locale)
                    || voices.find((voice) => voice.lang.startsWith('es') && voice.localService)
                    || voices.find((voice) => voice.lang.startsWith('es')) || null;
                utterance.onstart = () => { if (current === session) callbacks.state('playing'); };
                utterance.onend = ended;
                utterance.onerror = () => fail(new Error('Falló la lectura del navegador. La respuesta escrita sigue disponible.'));
                env.speechSynthesis.speak(utterance);
            } else {
                session.abort = new AbortController();
                const response = await checkedResponse(await env.fetch(config.speakUrl, {
                    method: 'POST', signal: session.abort.signal,
                    headers: { 'Content-Type': 'application/json', Accept: 'audio/mpeg, application/json', 'X-CSRF-TOKEN': config.csrf },
                    body: JSON.stringify({ text, consent: true }),
                }));
                const blob = await response.blob();
                if (current !== session) return;
                if (!blob.size || !blob.type.startsWith('audio/')) throw new Error('El proveedor no devolvió audio.');
                session.url = env.URL.createObjectURL(blob);
                session.audio = new env.Audio(session.url);
                session.audio.onended = ended;
                session.audio.onerror = () => fail(new Error('El navegador no pudo reproducir el audio recibido.'));
                await session.audio.play();
                if (current === session) callbacks.state('playing');
            }
        } catch (error) { fail(error); }
    }
    function pause() {
        if (!current) return;
        current.paused = true;
        if (current.utterance) env.speechSynthesis.pause();
        else current.audio?.pause();
    }
    return { play, pause, stop };
}
