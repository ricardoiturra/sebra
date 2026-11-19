<x-app-layout>
    <div class="sebra-dashboard min-h-[calc(100vh-4rem)] bg-slate-950 px-5 py-6 text-slate-100 sm:px-10">
        <header class="mx-auto mb-5 max-w-screen-2xl">
            <a href="{{ route('dashboard') }}" class="text-sm text-cyan-300 hover:underline">← Proyectos</a>
            <h1 class="mt-2 text-2xl font-semibold">{{ $project?->name ?? 'Grafo del sistema SeBra' }}</h1>
            @if ($project)
                <p class="mt-1 max-w-3xl whitespace-pre-wrap text-sm text-slate-300">{{ $project->objective }}</p>
            @endif
        </header>
        <div class="mx-auto grid max-w-screen-2xl gap-5 lg:grid-cols-[minmax(20rem,1fr)_minmax(0,1.35fr)]">
            <section class="min-w-0 rounded-2xl border border-slate-800 bg-slate-900 p-5 sm:p-6" aria-label="Chat sobre el grafo">
                @if ($project)
                    <p id="project-graph-empty" role="status" hidden class="mb-4 text-sm text-slate-400">Este proyecto todavía no tiene nodos.</p>
                @endif
                <form id="graph-query" data-url="{{ $project ? route('projects.ask', $project, false) : route('graph.query', [], false) }}">
                    <label for="graph-question" class="mb-2 block text-sm font-medium">Pregunta sobre el grafo</label>
                    <div class="flex flex-wrap gap-2">
                        <input id="graph-question" name="question" required maxlength="500" type="text" placeholder="¿Qué querés saber sobre este proyecto?" class="min-w-0 basis-full flex-1 rounded-lg border-slate-700 bg-slate-950 text-sm text-white sm:basis-auto">
                        <button id="voice-record" type="button" disabled aria-pressed="false" aria-label="Iniciar grabación" aria-describedby="voice-status voice-disclosure" class="rounded-lg border border-cyan-700 px-3 py-2 text-sm text-cyan-200 focus:ring-2 focus:ring-cyan-300 disabled:opacity-50">🎙 Micrófono</button>
                        <button type="submit" class="rounded-lg bg-cyan-300 px-4 py-2 text-sm font-semibold text-slate-950 disabled:opacity-50">Consultar a la IA</button>
                    </div>
                    <p id="graph-query-status" role="status" class="mt-2 break-words text-sm text-slate-300"></p>
                </form>

                <x-voice-controls :project="$project" />

                <details id="graph-answer" hidden class="relative mt-4 rounded-lg border border-slate-800 bg-slate-950 p-4">
                    <summary class="cursor-pointer pr-20 text-sm font-medium text-slate-200">Respuesta</summary>
                    <button id="graph-copy-answer" type="button" class="absolute right-3 top-2 rounded-lg border border-slate-700 px-3 py-1 text-xs font-medium text-slate-200 hover:bg-slate-800">Copiar</button>
                    <pre id="graph-answer-text" class="mt-3 max-h-[55vh] overflow-auto whitespace-pre-wrap break-words text-sm text-slate-300"></pre>
                </details>
                @if ($project)
                    <p id="project-ingestion-status" role="status" class="mt-2 text-sm text-slate-300 empty:hidden"></p>
                    @include('projects.notes')
                @endif
            </section>
            <section class="relative aspect-square w-full self-start overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-b from-slate-900 to-slate-950" aria-label="Grafo en tiempo real">
                <div class="absolute inset-0" data-graph-url="{{ $project ? route('projects.graph', $project, false) : route('sphere.graph', [], false) }}" data-node-url="{{ $project ? route('projects.node', $project, false) : route('sphere.node', [], false) }}" data-graph-events="{{ $project ? route('projects.events', $project, false) : route('sphere.events', [], false) }}"></div>
            </section>
        </div>
    </div>
</x-app-layout>
