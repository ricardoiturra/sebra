<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\GraphSelection;
use App\Support\ProjectGraph;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GraphNodeController extends Controller
{
    public function system(Request $request): JsonResponse
    {
        return $this->details($request, SphereGraphController::readGraph());
    }

    public function project(Request $request, Project $project): JsonResponse
    {
        return $this->details($request, ProjectGraph::forProject($project, forDisplay: true));
    }

    private function details(Request $request, array $graph): JsonResponse
    {
        $id = $request->validate(['id' => ['required', 'string', 'max:2000']])['id'];
        $nodes = array_column($graph['nodes'], null, 'id');
        abort_unless(isset($nodes[$id]), 404);
        $relations = array_values(array_filter(app(GraphSelection::class)->relations($graph),
            static fn (array $edge): bool => $edge['source'] === $id || $edge['target'] === $id));
        $neighbors = array_values(array_unique([...array_column($relations, 'source'), ...array_column($relations, 'target')]));
        $hyperedges = array_values(array_filter($graph['hyperedges'] ?? [],
            static fn (array $edge): bool => in_array($id, $edge['nodes'] ?? [], true)));

        return response()->json([
            'node' => $nodes[$id],
            'relations' => $relations,
            'neighbors' => array_values(array_map(static fn (string $neighbor): array => [
                'id' => $neighbor, 'label' => $nodes[$neighbor]['label'] ?? $neighbor,
            ], array_filter($neighbors, static fn (string $neighbor): bool => $neighbor !== $id && isset($nodes[$neighbor])))),
            'hyperedges' => $hyperedges,
        ])->header('Cache-Control', 'private, no-store');
    }
}
