<div class="mt-5 border-t border-slate-800 pt-4">
    <h2 id="project-notes" class="mb-2 mt-5 scroll-mt-4 text-sm font-semibold">Notas Markdown</h2>
    <form method="GET" action="{{ route('projects.show', $project) }}#project-notes" role="search" aria-label="Buscar nodos Markdown" class="mb-3">
        <label for="notes-search" class="sr-only">Buscar nodos Markdown</label>
        <div class="flex flex-wrap items-center gap-2">
            <input id="notes-search" type="search" name="q" maxlength="120" value="{{ $search }}" placeholder="Buscar por título o contenido" class="min-w-0 flex-1 rounded-lg border-slate-700 bg-slate-950 text-sm text-white">
            <button type="submit" class="rounded-lg border border-slate-700 px-3 py-2 text-sm hover:border-cyan-400">Buscar</button>
            @if ($search !== '')
                <a href="{{ route('projects.show', $project) }}#project-notes" class="px-2 py-2 text-sm text-cyan-300 hover:underline">Limpiar</a>
            @endif
        </div>
    </form>
    @if ($notes->isNotEmpty())
        <div class="space-y-2">
            @foreach ($notes as $note)
                <details class="rounded-lg border border-slate-800 bg-slate-950 p-3">
                    <summary class="cursor-pointer break-words text-sm">{{ $note->title }}</summary>
                    @if ($sourceUrl = $note->sourceUrl())
                        <a href="{{ $sourceUrl }}" class="mt-3 inline-block text-sm text-cyan-300 hover:underline">Fuente oficial</a>
                        <pre class="mt-2 max-h-72 overflow-auto whitespace-pre-wrap break-words text-sm">{{ $note->markdown }}</pre>
                    @else
                        <div class="prose prose-invert prose-sm mt-3 max-w-none break-words prose-pre:overflow-auto">
                            {!! Str::markdown($note->markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                        </div>
                    @endif
                </details>
            @endforeach
            @if ($moreResults)
                <p class="pt-2 text-sm text-slate-400">Se muestran las primeras {{ $notes->count() }} coincidencias. Afiná la búsqueda para encontrar otras.</p>
            @endif
        </div>
    @elseif ($search !== '')
        <p class="break-words text-sm text-slate-400">No se encontraron nodos para «{{ $search }}».</p>
    @endif
</div>
