<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Support\ActiveCommunities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SphereColorController extends Controller
{
    public const GRAPH_STREAM = 'sebra:sphere:graph';

    public const QUERY_STREAM = 'sebra:sphere:query';

    private const STREAMS = [
        'color' => 'sebra:sphere:colors',
        'direction' => 'sebra:sphere:directions',
        'graph' => self::GRAPH_STREAM,
        'query' => self::QUERY_STREAM,
        'active' => ActiveCommunities::STREAM,
    ];

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->isJson(), 415);

        $data = $request->validate([
            'color' => ['required', 'string', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ]);

        return $this->publish('color', strtolower($data['color']));
    }

    public function direction(Request $request): JsonResponse
    {
        abort_unless($request->isJson(), 415);

        $data = $request->validate([
            'direction' => ['required', 'integer', 'in:-1,1'],
        ]);

        return $this->publish('direction', (int) $data['direction']);
    }

    public function community(Request $request): JsonResponse
    {
        abort_unless($request->isJson(), 415);

        $input = $request->validate([
            'community' => ['sometimes', 'nullable', 'string'],
            'communities' => ['sometimes', 'array', 'list'],
            'communities.*' => ['required', 'string'],
        ]);
        if (array_key_exists('community', $input) === array_key_exists('communities', $input)) {
            throw ValidationException::withMessages(['communities' => 'Enviá community o communities.']);
        }

        $names = $input['communities'] ?? (isset($input['community']) ? [$input['community']] : []);
        $selected = [];

        if ($names !== []) {
            $available = [];
            foreach (SphereGraphController::readGraph()['nodes'] as $node) {
                $name = SphereGraphController::communityName($node);
                $available[mb_strtolower($name)] = $name;
            }
            foreach ($names as $name) {
                $canonical = $available[mb_strtolower($name)] ?? null;
                if ($canonical === null) {
                    throw ValidationException::withMessages(['communities' => "La comunidad {$name} no existe en el grafo actual."]);
                }
                $selected[$canonical] = $canonical;
            }
        }

        $communities = array_values($selected);
        $id = ActiveCommunities::update('set', $communities);

        return response()->json([
            'id' => $id,
            'communities' => $communities,
            ...(! array_key_exists('communities', $input) ? ['community' => $communities[0] ?? null] : []),
        ]);
    }

    private function publish(string $property, string|int|null $value): JsonResponse
    {
        $id = Redis::connection()->client()->executeRaw([
            'XADD', self::STREAMS[$property], 'MAXLEN', '256', '*', $property, (string) $value,
        ]);

        return response()->json(['id' => $id, $property => $value]);
    }

    public function events(Request $request): StreamedResponse
    {
        $userId = $request->user()->getKey();

        return $this->stream(self::STREAMS, fn (): bool => User::find($userId)?->hasRole('full admin') ?? false);
    }

    public function projectEvents(Request $request, Project $project): StreamedResponse
    {
        $prefix = $project->streamPrefix();
        $userId = $request->user()->getKey();
        $projectId = $project->getKey();

        $canRead = function () use ($userId, $projectId): bool {
            $user = User::find($userId);
            $project = Project::query()->select('id', 'team_id')->find($projectId);

            return $user !== null && $project !== null && Gate::forUser($user)->allows('view', $project);
        };

        return $this->stream([
            'graph' => $prefix.':graph',
            'query' => $prefix.':query',
            'active' => $prefix.':active',
            'ingestion' => $prefix.':ingestion',
        ], $canRead);
    }

    private function stream(array $streams, callable $canRead): StreamedResponse
    {
        return response()->stream(function () use ($streams, $canRead): void {
            if (! $canRead()) {
                return;
            }

            set_time_limit(0);

            $redis = Redis::connection()->client();
            $positions = array_fill_keys($streams, '0-0');

            echo ": connected\n\n";
            $this->flushOutput();

            foreach (array_keys($positions) as $stream) {
                $count = $stream === $streams['query'] ? '60' : '1';
                $latest = $redis->executeRaw(['XREVRANGE', $stream, '+', '-', 'COUNT', $count]);

                foreach (array_reverse($latest) as [$id, $fields]) {
                    $positions[$stream] = $id;
                    $this->sendEvent($id, ...$fields);
                }
            }

            while (! connection_aborted()) {
                // Redis blocks until a stream receives an event or a keepalive is due.
                $updates = $redis->executeRaw([
                    'XREAD', 'BLOCK', '15000', 'STREAMS', ...array_keys($positions), ...array_values($positions),
                ]);

                if (! $canRead()) {
                    return;
                }

                if ($updates === null) {
                    echo ": keepalive\n\n";
                    $this->flushOutput();

                    continue;
                }

                foreach ($updates as [$stream, $entries]) {
                    foreach ($entries as [$id, $fields]) {
                        $positions[$stream] = $id;
                        $this->sendEvent($id, ...$fields);
                    }
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sendEvent(string $id, string $property, string $value): void
    {
        $payload = match ($property) {
            'direction' => (int) $value,
            'query', 'active' => json_decode($value, true, flags: JSON_THROW_ON_ERROR),
            default => $value,
        };
        $data = json_encode([$property => $payload], JSON_THROW_ON_ERROR);

        echo "id: {$id}\nevent: sphere-{$property}\ndata: {$data}\n\n";
        $this->flushOutput();
    }

    private function flushOutput(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
