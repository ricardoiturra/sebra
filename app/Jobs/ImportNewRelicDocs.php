<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\User;
use App\Support\ProjectGraph;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ImportNewRelicDocs implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 7200;

    public int $tries = 1;

    public int $uniqueFor = 10800;

    public function __construct(public int $projectId, public int $userId) {}

    public function uniqueId(): string
    {
        return (string) $this->projectId;
    }

    public function handle(): void
    {
        $project = Project::find($this->projectId);
        if (! $this->canImport($project)) {
            return;
        }

        $api = Http::acceptJson()->withoutRedirecting()->timeout(35);
        $head = $api->get('https://api.github.com/repos/newrelic/docs-website/commits/develop')->throw()->json();
        $commit = $head['sha'] ?? '';
        $treeId = $head['commit']['tree']['sha'] ?? '';
        if (! preg_match('/\A[a-f0-9]{40}\z/', $commit) || ! preg_match('/\A[a-f0-9]{40}\z/', $treeId)) {
            throw new RuntimeException('No se pudo identificar la revisión oficial de New Relic.');
        }

        $tree = $api->get("https://api.github.com/repos/newrelic/docs-website/git/trees/{$treeId}", ['recursive' => 1])
            ->throw()->json();
        if (($tree['truncated'] ?? true) || ! is_array($tree['tree'] ?? null)) {
            throw new RuntimeException('El índice de New Relic está incompleto.');
        }

        $docs = array_filter($tree['tree'], fn (array $entry): bool => ($entry['type'] ?? null) === 'blob'
            && preg_match('~\Asrc/content/docs/(?:[\w &.@()+,-]+/)*[\w &.@()+,-]+\.mdx?\z~u', $entry['path'] ?? '')
            && preg_match('/\A[a-f0-9]{40}\z/', $entry['sha'] ?? ''));
        $known = $project->notes()->where('source', 'like', 'newrelic:%')->pluck('revision', 'source');
        $changed = 0;
        $failed = 0;
        $processed = 0;
        $published = 0;
        $total = count($docs);

        ProjectGraph::publishImportStatus($project, "New Relic: 0 de {$total} documentos revisados.");

        foreach (array_chunk($docs, 24) as $batch) {
            if (! $this->canImport($project->fresh())) {
                ProjectGraph::publishImportStatus($project, 'Importación detenida. El usuario ya no tiene permiso.');

                return;
            }

            $pending = array_filter($batch, fn (array $entry): bool => $known->get('newrelic:'.$entry['path']) !== $entry['sha']);
            if ($pending !== []) {
                $responses = Http::pool(fn (Pool $pool): array => array_map(function (array $entry) use ($pool, $commit) {
                    $path = implode('/', array_map(rawurlencode(...), explode('/', $entry['path'])));

                    return $pool->as($entry['path'])->withoutRedirecting()->connectTimeout(5)->timeout(25)
                        ->get("https://raw.githubusercontent.com/newrelic/docs-website/{$commit}/{$path}");
                }, $pending), concurrency: 12);

                $rows = [];
                foreach ($pending as $entry) {
                    $response = $responses[$entry['path']] ?? null;
                    if (! $response instanceof Response || ! $response->successful()) {
                        $failed++;

                        continue;
                    }

                    $body = $response->body();
                    if (sha1('blob '.strlen($body)."\0".$body) !== $entry['sha']) {
                        $failed++;

                        continue;
                    }

                    preg_match('/^title:\s*([^\r\n]+)/m', $body, $title);
                    $rows[] = [
                        'project_id' => $project->id,
                        'source' => 'newrelic:'.$entry['path'],
                        'revision' => $entry['sha'],
                        'title' => mb_substr(trim($title[1] ?? str_replace('-', ' ', pathinfo($entry['path'], PATHINFO_FILENAME)), " \"'"), 0, 120),
                        'markdown' => $body,
                    ];
                }

                if ($rows !== []) {
                    $project->notes()->upsert($rows, ['project_id', 'source'], ['title', 'markdown', 'revision']);
                    $changed += count($rows);
                }
            }
            $processed += count($batch);
            if ($processed % 240 === 0) {
                if ($changed > $published) {
                    ProjectGraph::notifyChanged($project);
                    $published = $changed;
                }
                ProjectGraph::publishImportStatus($project, "New Relic: {$processed} de {$total} documentos revisados.");
            }
        }

        if ($changed > 0) {
            ProjectGraph::notifyChanged($project);
        }

        $message = "New Relic: {$total} documentos revisados. {$changed} nuevos o actualizados.";
        if ($failed > 0) {
            $message .= " {$failed} descargas pendientes. Volvé a pedir la importación para reintentarlas.";
        }
        ProjectGraph::publishImportStatus($project, $message);
    }

    public function failed(?Throwable $exception): void
    {
        if ($project = Project::find($this->projectId)) {
            ProjectGraph::notifyChanged($project);
            ProjectGraph::publishImportStatus($project, 'La importación de New Relic se interrumpió. Podés reanudarla con el mismo pedido.');
        }
    }

    private function canImport(?Project $project): bool
    {
        $user = User::find($this->userId);

        return $project !== null && $user !== null && Gate::forUser($user)->allows('updateGraph', $project);
    }
}
