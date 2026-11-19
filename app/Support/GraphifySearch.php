<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class GraphifySearch
{
    /** @return array{int, string, array} */
    public function search(GraphQuery $query, string $path, callable $progress): array
    {
        if ($query->intent === 'modify' || $query->ambiguous || $query->terms === []) {
            throw new RuntimeException('La consulta de lectura no está resuelta.');
        }
        $pending = '';
        $selection = [];
        $process = new Process([
            config('services.graphify.python', '/opt/graphify/bin/python'),
            base_path('scripts/graphify_query.py'), '--sebra-query', $path,
        ], base_path(), ['GRAPHIFY_QUERY_LOG_DISABLE' => '1']);
        $process->setInput(json_encode(['intent' => $query->intent, 'terms' => $query->terms, 'ambiguous' => $query->ambiguous], JSON_THROW_ON_ERROR));
        $process->setTimeout(12);

        try {
            $exit = $process->run(function (string $type, string $chunk) use ($progress, &$pending, &$selection): void {
                if ($type !== Process::ERR) {
                    return;
                }
                $lines = explode("\n", $pending.$chunk);
                $pending = array_pop($lines);
                foreach ($lines as $line) {
                    if (str_starts_with($line, 'SEBRA_SELECTION ')) {
                        $selection = json_decode(substr($line, 16), true, flags: JSON_THROW_ON_ERROR);
                    }
                    if (str_starts_with($line, 'SEBRA_PROGRESS ')) {
                        $progress(json_decode(substr($line, 15), true, flags: JSON_THROW_ON_ERROR));
                    }
                }
            });
        } catch (ProcessTimedOutException) {
            $exit = 1;
        }

        return [$exit, rtrim($process->getOutput()), $selection];
    }
}
