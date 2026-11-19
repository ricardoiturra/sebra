@props(['project' => null])
@php
    $voiceConfig = [
        'stt' => config('audio.stt.provider'), 'tts' => config('audio.tts.provider'),
        'locale' => config('audio.locale'), 'maxSeconds' => config('audio.max_seconds'),
        'maxBytes' => config('audio.max_kilobytes') * 1024,
        'transcribeUrl' => $project ? route('projects.audio.transcribe', $project, false) : route('audio.transcribe', [], false),
        'speakUrl' => $project ? route('projects.audio.speak', $project, false) : route('audio.speak', [], false),
    ];
@endphp
<div data-voice-config="{{ json_encode($voiceConfig, JSON_THROW_ON_ERROR) }}" data-state="ready" class="mt-4 rounded-lg border border-slate-700 bg-slate-950 p-3 text-sm text-slate-300">
    <p id="voice-disclosure" class="mb-3 text-xs leading-relaxed text-slate-400">
        Voz opcional, por turnos y hasta {{ config('audio.max_seconds') }} segundos. No guardamos el audio en SeBra.
        @if (config('audio.stt.provider') === 'browser')
            El reconocimiento del navegador puede enviar tu voz a su proveedor; no se garantiza procesamiento local.
        @elseif (config('audio.stt.provider') === 'compatible')
            {{ config('audio.stt.disclosure') }}
        @else
            Entrada de voz deshabilitada.
        @endif
        @if (config('audio.tts.provider') === 'compatible')
            {{ config('audio.tts.disclosure') }}
        @elseif (config('audio.tts.provider') === 'browser')
            La lectura usa las voces disponibles del navegador, que también pueden ser remotas.
        @endif
        La voz de respuesta es sintética. Las referencias completas permanecen en la respuesta escrita.
    </p>
    <div class="flex flex-wrap gap-x-4 gap-y-3">
        <label class="flex items-center gap-2"><input id="voice-consent" type="checkbox" class="rounded border-slate-600 bg-slate-900 text-cyan-400 focus:ring-cyan-300">Permitir voz según este aviso</label>
        <label class="flex items-center gap-2"><input id="voice-auto-send" type="checkbox" class="rounded border-slate-600 bg-slate-900 text-cyan-400 focus:ring-cyan-300">Enviar transcripción automáticamente</label>
        <label class="flex items-center gap-2"><input id="voice-auto-read" type="checkbox" class="rounded border-slate-600 bg-slate-900 text-cyan-400 focus:ring-cyan-300">Leer respuestas automáticamente</label>
    </div>
    <div class="mt-3 flex flex-wrap gap-2" role="group" aria-label="Controles de voz">
        <button id="voice-cancel" type="button" hidden class="rounded-lg border border-red-700 px-3 py-2 text-red-200">Cancelar grabación</button>
        <button id="voice-play" type="button" disabled class="rounded-lg border border-slate-600 px-3 py-2 focus:ring-2 focus:ring-cyan-300 disabled:opacity-50">Reproducir / reanudar</button>
        <button id="voice-pause" type="button" disabled class="rounded-lg border border-slate-600 px-3 py-2 focus:ring-2 focus:ring-cyan-300 disabled:opacity-50">Pausar lectura</button>
        <button id="voice-stop" type="button" disabled class="rounded-lg border border-slate-600 px-3 py-2 focus:ring-2 focus:ring-cyan-300 disabled:opacity-50">Detener lectura</button>
    </div>
    <p id="voice-status" role="status" aria-live="polite" class="mt-3 min-h-5 text-cyan-200">La voz es opcional. Podés seguir usando el teclado.</p>
</div>
