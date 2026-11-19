<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectGraph;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $team = $request->user()->currentTeam;
        if ($team !== null) {
            Gate::authorize('view', $team);
        }

        return view('dashboard', [
            'team' => $team,
            'projects' => $team?->projects()->select('id', 'name', 'objective')->latest('id')->paginate(12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($team === null, 403);
        Gate::authorize('view', $team);

        $project = $team->projects()->create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'objective' => ['required', 'string', 'max:2000'],
        ]));

        return redirect()->route('projects.show', $project);
    }

    public function show(Request $request, Project $project): View
    {
        $input = $request->query('q');
        $search = is_string($input) ? mb_substr(trim($input), 0, 120) : '';
        $notes = collect();
        $limit = 30;

        if ($search !== '') {
            $pattern = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $notes = $project->notes()
                ->where(function ($query) use ($pattern): void {
                    $query->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("markdown LIKE ? ESCAPE '!'", [$pattern]);
                })
                ->latest('id')->limit($limit + 1)->get();
        }

        return view('projects.show', [
            'project' => $project,
            'notes' => $notes->take($limit),
            'moreResults' => $notes->count() > $limit,
            'search' => $search,
        ]);
    }

    public function storeNote(Request $request, Project $project): RedirectResponse
    {
        $project->notes()->create($request->validate([
            'title' => ['required', 'string', 'max:120'],
            'markdown' => ['required', 'string', 'max:12000', 'regex:/\S/u'],
        ]));

        ProjectGraph::notifyChanged($project);

        return redirect()->route('projects.show', $project)->with('note-created', 'Nota añadida al grafo.');
    }

    public function importGraph(Request $request, Project $project): RedirectResponse
    {
        $file = $request->validate(['graph' => ['required', 'file', 'max:4096']])['graph'];
        $contents = $file->get();
        $project->update(['graph' => ProjectGraph::parse($contents)]);

        ProjectGraph::notifyChanged($project);

        return back()->with('graph-imported', 'Grafo del proyecto actualizado.');
    }
}
