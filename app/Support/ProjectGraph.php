<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;

final class ProjectGraph
{
    public static function forProject(Project $project, bool $forDisplay = false): array
    {
        $graph = ($project->graph ?? []) + ['nodes' => [], 'links' => []];
        $ids = array_fill_keys(array_column($graph['nodes'], 'id'), true);

        foreach ($graph['nodes'] as &$node) {
            unset($node['display_id']);
        }
        unset($node);
        $sectionDisplayIds = [];
        $sections = [];
        foreach ($project->notes()->orderBy('id')->select('id', 'title', 'source')->cursor() as $note) {
            $section = str_starts_with($note->source ?? '', 'newrelic:') ? explode('/', $note->source)[3] : null;
            $community = $section === null ? 'Notas Markdown' : 'New Relic · '.Str::headline($section);
            $id = 'sebra-note:'.$note->id;

            if ($section !== null && ! isset($sectionDisplayIds[$section])) {
                $displayId = 'sebra-newrelic:'.$section;
                while (isset($ids[$displayId])) {
                    $displayId .= ':note';
                }
                $sectionDisplayIds[$section] = $displayId;
            }

            if ($forDisplay && $section !== null) {
                if (isset($sections[$section])) {
                    continue;
                }

                $sections[$section] = true;
                $id = $sectionDisplayIds[$section];
            }

            while (isset($ids[$id])) {
                $id .= ':note';
            }

            $graph['nodes'][] = [
                'id' => $id,
                'display_id' => $section === null ? $id : $sectionDisplayIds[$section],
                'label' => $forDisplay && $section !== null ? $community : $note->title,
                'community' => $section === null ? 'markdown-notes' : 'newrelic:'.$section,
                'community_name' => $community,
                'file_type' => 'document',
                'source_file' => 'sebra-notes/'.$note->id.'.md',
            ];
        }

        return $graph;
    }

    public static function noteContext(Project $project, string $answer): string
    {
        preg_match_all('/^NODE .* \[src=sebra-notes\/(\d+)\.md loc=/m', $answer, $matches);
        $ids = array_slice(array_values(array_unique($matches[1])), 0, 4);
        $notes = $project->notes()->whereKey($ids)->get()->keyBy('id');
        $context = '';

        foreach ($ids as $id) {
            if ($note = $notes->get($id)) {
                $source = $note->sourceUrl() ?? "sebra-notes/{$id}.md";
                $context .= "\n\nNota {$source} — {$note->title}\n".mb_substr($note->markdown, 0, 3000);
            }
        }

        return $context;
    }

    public static function notifyChanged(Project $project): void
    {
        self::publish($project, 'graph', (string) Str::uuid());
    }

    public static function publishImportStatus(Project $project, string $message): void
    {
        self::publish($project, 'ingestion', $message);
    }

    private static function publish(Project $project, string $type, string $value): void
    {
        try {
            Redis::connection()->client()->executeRaw([
                'XADD', $project->streamPrefix().':'.$type, 'MAXLEN', '1', '*', $type, $value,
            ]);
        } catch (\Exception $error) {
            report($error);
        }
    }

    public static function parse(string $contents): array
    {
        try {
            $graph = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['graph' => 'El archivo debe ser un graph.json válido de Graphify.']);
        }

        if (! is_array($graph)) {
            throw ValidationException::withMessages(['graph' => 'El archivo debe contener un grafo de Graphify.']);
        }

        $graph['links'] ??= $graph['edges'] ?? null;
        $graph['hyperedges'] ??= [];
        Validator::make($graph, [
            'nodes' => ['present', 'array', 'list'],
            'nodes.*' => ['required', 'array'],
            'nodes.*.id' => ['required', 'string', 'distinct:strict'],
            'nodes.*.label' => ['sometimes', 'nullable', 'string'],
            'nodes.*.community' => ['sometimes', 'nullable', 'integer'],
            'nodes.*.community_name' => ['sometimes', 'nullable', 'string'],
            'links' => ['present', 'array', 'list'],
            'links.*' => ['required', 'array'],
            'links.*.source' => ['required', 'string'],
            'links.*.target' => ['required', 'string'],
            'hyperedges' => ['present', 'array', 'list'],
            'hyperedges.*' => ['required', 'array'],
            'hyperedges.*.nodes' => ['required', 'array', 'list'],
            'hyperedges.*.nodes.*' => ['required', 'string'],
            'graph' => ['sometimes', 'array'],
            'directed' => ['sometimes', 'boolean'],
            'multigraph' => ['sometimes', 'boolean'],
        ])->validate();

        $ids = array_fill_keys(array_column($graph['nodes'], 'id'), true);
        foreach ($graph['links'] as $link) {
            self::requireNodes([$link['source'], $link['target']], $ids);
        }
        foreach ($graph['hyperedges'] as $edge) {
            self::requireNodes($edge['nodes'], $ids);
        }

        return $graph;
    }

    private static function requireNodes(array $endpoints, array $ids): void
    {
        foreach ($endpoints as $id) {
            if (! isset($ids[$id])) {
                throw ValidationException::withMessages(['graph' => 'Hay relaciones que apuntan a nodos inexistentes.']);
            }
        }
    }
}
