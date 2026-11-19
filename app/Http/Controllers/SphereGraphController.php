<?php

namespace App\Http\Controllers;

use App\Jobs\ImportNewRelicDocs;
use App\Models\Project;
use App\Support\ActiveCommunities;
use App\Support\Audio\SpokenText;
use App\Support\GraphifySearch;
use App\Support\GraphInference;
use App\Support\GraphQueryInterpreter;
use App\Support\GraphSelection;
use App\Support\ProjectGraph;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RuntimeException;

class SphereGraphController extends Controller
{
    public function __construct(private GraphQueryInterpreter $interpreter, private GraphifySearch $search, private SpokenText $spokenText, private GraphSelection $graphSelection) {}

    public function __invoke(): JsonResponse
    {
        return $this->graphResponse(self::readGraph());
    }

    public function projectGraph(Project $project): JsonResponse
    {
        return $this->graphResponse(ProjectGraph::forProject($project, forDisplay: true));
    }

    private function graphResponse(array $graph): JsonResponse
    {
        return response()->json([
            'nodes' => array_map(static fn (array $node): array => [
                'id' => $node['id'],
                'community' => $node['community'] ?? 'unknown',
                'name' => self::communityName($node),
                ...(isset($node['label']) ? ['label' => $node['label']] : []),
            ], $graph['nodes']),
            'links' => array_map(static fn (array $link): array => [
                $link['source'], $link['target'],
            ], $graph['links']),
            'relations' => $this->graphSelection->relations($graph),
            'hyperedges' => array_column($graph['hyperedges'] ?? [], 'nodes'),
        ], 200, ['Cache-Control' => 'no-store']);
    }

    public function query(Request $request, GraphInference $inference): JsonResponse
    {
        return $this->runQuery($request, $inference, synthesize: false);
    }

    public function ask(Request $request, GraphInference $inference): JsonResponse
    {
        $this->checkInference($inference);

        return $this->runQuery($request, $inference);
    }

    public function askProject(Request $request, Project $project, GraphInference $inference): JsonResponse
    {
        $graph = ProjectGraph::forProject($project);
        abort_if(empty($graph['nodes']), 422, 'Añadí una nota o importá un grafo para consultar este proyecto.');
        $this->checkInference($inference);

        $directory = sys_get_temp_dir().'/sebra-project-'.Str::uuid();
        throw_unless(mkdir($directory, 0700), RuntimeException::class, 'No se pudo preparar el grafo del proyecto.');
        $path = $directory.'/graph.json';

        try {
            $graph += ['directed' => false, 'multigraph' => false];
            $graph['graph'] = (object) ($graph['graph'] ?? []);
            throw_if(file_put_contents($path, json_encode($graph, JSON_THROW_ON_ERROR)) === false, RuntimeException::class, 'No se pudo leer el grafo del proyecto.');

            return $this->runQuery($request, $inference, $project, $path, graph: $graph);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function modifyProject(Request $request, Project $project, GraphInference $inference): JsonResponse
    {
        $question = $request->validate(['question' => ['required', 'string', 'max:500']])['question'];
        $action = Str::ascii(mb_strtolower($question));

        if (preg_match('/\A(?:por favor[, ]+)?(?:descarga|descargame|importe|importa|actualiza)\b.{0,400}\b(?:docs|documentacion)\b/', $action)
            && preg_match('/\bnew\s*relic\b/', $action)) {
            Gate::authorize('updateGraph', $project);
            ImportNewRelicDocs::dispatch($project->id, $request->user()->id)->onConnection('project-imports');

            return response()->json(['message' => 'Importación solicitada. El progreso aparecerá en este proyecto.'], 202);
        }

        if (preg_match('/\A(?:por favor[, ]+)?(?:crea\w*|anadi\w*|anade\w*|agrega\w*|genera\w*|guarda\w*)\b.{0,100}\b(?:nota[s]?|nodo[s]?)\b/', $action)) {
            return $this->createAiNote($project, $inference, $question);
        }

        abort(422, 'La acción solicitada no está disponible.');
    }

    private function createAiNote(Project $project, GraphInference $inference, string $question): JsonResponse
    {
        $this->checkInference($inference);

        try {
            ['answer' => $draft] = $inference->draftNote($question);
        } catch (RuntimeException $error) {
            abort(502, $error->getMessage());
        }

        $draft = trim(preg_replace('/\A```(?:json)?\s*|\s*```\z/i', '', $draft));
        $note = json_decode($draft, true);
        abort_unless(is_string($note['title'] ?? null) && is_string($note['markdown'] ?? null), 502, 'La IA no devolvió una nota válida.');
        $title = trim($note['title']);
        $markdown = trim($note['markdown']);
        abort_if($title === '' || $markdown === '' || mb_strlen($title) > 120 || mb_strlen($markdown) > 12000, 502, 'La nota de la IA está vacía o excede el límite.');

        $digest = hash('sha256', Str::squish(mb_strtolower($title))."\n".Str::squish(mb_strtolower($markdown)));
        $note = $project->notes()->firstOrCreate(['source' => 'ai:'.$digest], ['title' => $title, 'markdown' => $markdown]);
        if ($note->wasRecentlyCreated) {
            ProjectGraph::notifyChanged($project);
        }

        return response()->json([
            'answer' => $note->markdown,
            'message' => $note->wasRecentlyCreated ? 'Nodo añadido al grafo.' : 'Ya existe un nodo con ese contenido.',
            'note_id' => $note->id,
        ]);
    }

    private function checkInference(GraphInference $inference): void
    {
        try {
            $inference->checkConfiguration();
        } catch (RuntimeException $error) {
            abort(503, $error->getMessage());
        }
    }

    private function runQuery(Request $request, ?GraphInference $inference = null, ?Project $project = null, ?string $path = null, bool $synthesize = true, ?array $graph = null): JsonResponse
    {
        $question = $request->validate(['question' => ['required', 'string', 'max:500']])['question'];
        $graph ??= $project === null ? self::readGraph() : ProjectGraph::forProject($project);
        $path ??= base_path('graphify-out/graph.json');
        $prefix = $project?->streamPrefix() ?? 'sebra:sphere';
        $queryId = (string) Str::uuid();
        $this->publishQuery($prefix, $queryId, 'started', [], question: $question);
        $communities = [];
        try {
            $interpretation = $this->interpreter->interpret($question, $graph, $inference);
            $retrieval = match (true) {
                $interpretation->intent === 'modify' => [5, ''],
                $interpretation->ambiguous => [3, ''],
                $interpretation->terms === [] => [4, ''],
                default => $this->search->search($interpretation, $path, function (array $batch) use ($prefix, $queryId, &$communities): void {
                    foreach ($batch as $community) {
                        $communities[$community['id']] = $community;
                    }
                    $this->publishQuery($prefix, $queryId, 'progress', $communities);
                }),
            };
            [$exit, $answer] = $retrieval;
        } catch (RuntimeException $error) {
            $this->publishQuery($prefix, $queryId, 'error', $communities, $error->getMessage(), $question);
            abort(502, $error->getMessage());
        }

        if ($interpretation->intent === 'modify') {
            $this->publishQuery($prefix, $queryId, 'error', [], 'Consulta de solo lectura.', $question);
            abort(422, 'Esta consulta es de solo lectura. Usá una acción explícita para modificar datos.');
        }

        if (! in_array($exit, [0, 3, 4], true)) {
            $message = 'Graphify no pudo ejecutar la consulta.';
            $this->publishQuery($prefix, $queryId, 'error', [], $message, $question);
            abort(503, $message);
        }

        $message = match ($exit) {
            3 => 'La consulta es ambigua. Especificá el nombre o la ruta del archivo.',
            4 => 'Graphify no encontró nodos para esta consulta.',
            default => null,
        };

        if ($exit === 3) {
            $answer = $message.' Opciones: '.implode(', ', array_map(fn (array $entity): string => $entity['label'].' ('.$entity['source'].')', $interpretation->entities));
        }
        if ($exit === 4) {
            $answer = $message = 'No encontré referencias en el grafo para esa pregunta. Probá con un archivo, un componente o un tema del proyecto.';
        }
        $model = null;
        $provider = null;
        if ($synthesize && $inference !== null && $exit === 0 && str_contains($answer, 'NODE ')) {
            $this->publishQuery($prefix, $queryId, 'answering', $communities);
            try {
                $context = $answer;
                if ($project !== null) {
                    $context .= ProjectGraph::noteContext($project, $answer);
                }
                ['answer' => $answer, 'model' => $model, 'provider' => $provider] = $inference->answer($question, $context, $interpretation);
            } catch (RuntimeException $error) {
                $this->publishQuery($prefix, $queryId, 'error', $communities, $error->getMessage(), $question);
                abort(502, $error->getMessage());
            }
        }
        $selection = $this->graphSelection->build($graph, $exit === 0 ? ($retrieval[2] ?? []) : [], $interpretation->navigation);
        if ($exit === 3) {
            $selection['status'] = 'ambiguous';
            $selection['message'] = $message;
        }
        $eventId = $this->publishQuery($prefix, $queryId, 'complete', $communities, $message, $question, $answer, $model, $provider);

        return response()->json([
            'selection' => $selection,
            'speech_text' => $this->spokenText->prepare($answer),
            'interpretation' => $interpretation->toArray(),
            'query_id' => $queryId,
            'event_id' => $eventId,
            'phase' => 'complete',
            'question' => $question,
            'answer' => $answer,
            'model' => $model,
            'provider' => $provider,
            'communities' => array_values($communities),
            'message' => $message,
        ]);
    }

    private function publishQuery(
        string $prefix, string $id, string $phase, array $communities, ?string $message = null,
        ?string $question = null, ?string $answer = null, ?string $model = null, ?string $provider = null,
    ): string {
        ActiveCommunities::update($phase, array_values(array_unique(array_column($communities, 'name'))), $id, $prefix);

        $payload = [
            'query_id' => $id,
            'phase' => $phase,
            'question' => $question,
            'answer' => $answer,
            'model' => $model,
            'provider' => $provider,
            'communities' => array_values($communities),
            'message' => $message,
        ];

        return Redis::connection()->client()->executeRaw([
            'XADD', $prefix.':query', 'MAXLEN', '60', '*',
            'query', json_encode($payload, JSON_THROW_ON_ERROR),
        ]);
    }

    public static function communityName(array $node): string
    {
        return $node['community_name'] ?? 'Comunidad '.($node['community'] ?? 'unknown');
    }

    public static function readGraph(): array
    {
        $path = base_path('graphify-out/graph.json');
        abort_unless(is_readable($path), 404);

        $graph = json_decode(file_get_contents($path), true);
        abort_unless(isset($graph['nodes'], $graph['links']), 503);

        return $graph;
    }
}
