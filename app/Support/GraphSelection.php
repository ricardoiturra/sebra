<?php

namespace App\Support;

final class GraphSelection
{
    public function relations(array $graph): array
    {
        $relations = [];
        $known = array_fill_keys(array_column($graph['nodes'], 'id'), true);
        foreach ($graph['links'] as $link) {
            $source = (string) $link['source'];
            $target = (string) $link['target'];
            if (! isset($known[$source], $known[$target])) {
                continue;
            }
            $type = is_string($link['relation'] ?? null) ? $link['relation'] : '';
            $confidence = is_scalar($link['confidence'] ?? null) ? (string) $link['confidence'] : '';
            $directed = (bool) ($graph['directed'] ?? false);
            $relations[] = [
                'id' => 'edge:'.substr(hash('sha256', json_encode([$source, $target, $type, $confidence, $directed], JSON_THROW_ON_ERROR)), 0, 24),
                'source' => $source, 'target' => $target, 'relation' => $type,
                'directed' => $directed, 'confidence' => $confidence,
            ];
        }

        return $relations;
    }

    public function build(array $graph, array $traversal, string $mode = 'related'): array
    {
        $nodes = array_column($graph['nodes'], null, 'id');
        $allowed = static fn (mixed $id): bool => is_string($id) && isset($nodes[$id]);
        $seeds = array_values(array_unique(array_filter(is_array($traversal['seed_ids'] ?? null) ? $traversal['seed_ids'] : [], $allowed)));
        $ids = array_values(array_unique(array_filter([...$seeds, ...(is_array($traversal['node_ids'] ?? null) ? $traversal['node_ids'] : [])], $allowed)));
        $ids = array_slice($ids, 0, 200);
        $pairs = [];
        foreach (is_array($traversal['edges'] ?? null) ? $traversal['edges'] : [] as $edge) {
            if (is_array($edge) && count($edge) === 2 && isset($edge[0], $edge[1]) && $allowed($edge[0]) && $allowed($edge[1])) {
                $pairs[json_encode($edge)] = true;
            }
        }
        $relations = array_values(array_filter($this->relations($graph), static function (array $edge) use ($ids, $pairs): bool {
            return in_array($edge['source'], $ids, true) && in_array($edge['target'], $ids, true)
                && (isset($pairs[json_encode([$edge['source'], $edge['target']])])
                    || isset($pairs[json_encode([$edge['target'], $edge['source']])]));
        }));
        $message = null;
        if ($mode === 'focus') {
            $ids = $seeds;
            $relations = [];
        } elseif ($mode === 'dependencies') {
            if ($graph['directed'] ?? false) {
                $relations = array_values(array_filter($relations, static fn (array $edge): bool => in_array($edge['target'], $seeds, true)));
                $ids = array_values(array_unique([...$seeds, ...array_column($relations, 'source')]));
                $message = 'Se muestran relaciones entrantes verificadas. El tipo de cada relación determina su significado.';
            } else {
                $message = 'El grafo no declara dirección: se muestran relaciones verificadas sin afirmar dependencias.';
            }
        } elseif ($mode === 'path' && count($seeds) >= 2) {
            $path = $this->path($seeds[0], $seeds[1], $relations);
            $ids = $path ?: array_slice($seeds, 0, 2);
            $relations = array_values(array_filter($relations, static fn (array $edge): bool => in_array($edge['source'], $ids, true) && in_array($edge['target'], $ids, true)));
            if ($path === []) {
                $message = 'No se encontró un camino entre las entidades en el subgrafo recuperado.';
            }
        }
        if ($mode === 'path' && count($seeds) < 2) {
            $ids = $seeds;
            $relations = [];
            $message = 'No se confirmaron dos entidades distintas para buscar una relación.';
        }
        $display = static fn (string $id): string => (string) ($nodes[$id]['display_id'] ?? $id);

        return [
            'version' => 1, 'status' => $ids === [] ? 'none' : 'matched', 'mode' => $mode,
            'node_ids' => $ids, 'seed_ids' => $seeds,
            'display_node_ids' => array_values(array_unique(array_map($display, $ids))),
            'focus_ids' => array_values(array_unique(array_map($display, ($mode === 'path' ? $ids : array_values(array_intersect($seeds, $ids)))))),
            'relations' => array_slice($relations, 0, 400),
            'truncated' => ($traversal['truncated'] ?? false) || count($relations) > 400,
            'message' => $message,
        ];
    }

    /** @return list<string> */
    private function path(string $start, string $end, array $relations): array
    {
        $adjacent = [];
        foreach ($relations as $edge) {
            $adjacent[$edge['source']][] = $edge['target'];
            if (! $edge['directed']) {
                $adjacent[$edge['target']][] = $edge['source'];
            }
        }
        $queue = [$start];
        $previous = [$start => null];
        for ($index = 0; $index < count($queue); $index++) {
            $node = $queue[$index];
            if ($node === $end) {
                $path = [];
                for ($at = $end; $at !== null; $at = $previous[$at]) {
                    array_unshift($path, $at);
                }

                return $path;
            }
            foreach ($adjacent[$node] ?? [] as $neighbor) {
                if (! array_key_exists($neighbor, $previous)) {
                    $previous[$neighbor] = $node;
                    $queue[] = $neighbor;
                }
            }
        }

        return [];
    }
}
