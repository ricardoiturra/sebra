<x-app-layout>
    <div class="sebra-dashboard min-h-[calc(100vh-4rem)] bg-slate-950 px-5 py-8 text-slate-100 sm:px-10">
        <div class="mx-auto max-w-6xl">
            <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">Proyectos</h1>
                    @if ($team)
                        <p class="mt-1 text-sm text-slate-400">{{ $team->name }}</p>
                    @endif
                </div>
                @role('full admin')
                    <a href="{{ route('projects.system') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm hover:border-cyan-400">Grafo del sistema SeBra</a>
                @endrole
            </header>

            @if ($team)
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_22rem]">
                    <section aria-label="Proyectos del equipo" class="min-w-0">
                        @forelse ($projects as $project)
                            <a href="{{ route('projects.show', $project) }}" class="mb-3 block rounded-xl border border-slate-800 bg-slate-900 p-4 hover:border-cyan-400">
                                <h2 class="break-words font-semibold text-cyan-200">{{ $project->name }}</h2>
                                <p class="mt-2 break-words text-sm text-slate-300">{{ $project->objective }}</p>
                            </a>
                        @empty
                            <p class="rounded-xl border border-slate-800 bg-slate-900 p-4 text-sm text-slate-400">Este equipo todavía no tiene proyectos.</p>
                        @endforelse
                        {{ $projects->links() }}
                    </section>

                    <form method="POST" action="{{ route('projects.store') }}" aria-label="Crear proyecto" class="self-start rounded-xl border border-slate-800 bg-slate-900 p-5">
                        @csrf
                        <h2 class="mb-4 font-semibold">Nuevo proyecto</h2>
                        <label for="project-name" class="block text-sm">Nombre</label>
                        <input id="project-name" name="name" value="{{ old('name') }}" maxlength="120" required class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950">
                        @error('name')
                            <p role="alert" class="mt-1 text-sm text-red-300">{{ $message }}</p>
                        @enderror

                        <label for="project-objective" class="mt-4 block text-sm">Objetivo</label>
                        <textarea id="project-objective" name="objective" rows="3" maxlength="2000" required class="mt-1 w-full rounded-lg border-slate-700 bg-slate-950">{{ old('objective') }}</textarea>
                        @error('objective')
                            <p role="alert" class="mt-1 text-sm text-red-300">{{ $message }}</p>
                        @enderror

                        <button class="mt-4 rounded-lg bg-cyan-300 px-4 py-2 text-sm font-semibold text-slate-950">Crear proyecto</button>
                    </form>
                </div>
            @else
                <p class="text-sm text-slate-400">Seleccioná un equipo para ver sus proyectos.</p>
            @endif
        </div>
    </div>
</x-app-layout>
