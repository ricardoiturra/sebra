<?php

namespace Tests\Feature;

use App\Support\GraphifySearch;
use App\Support\GraphQuery;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class GraphifySearchTest extends TestCase
{
    public function test_structured_queries_run_through_the_real_python_adapter(): void
    {
        if (! is_executable(config('services.graphify.python'))) {
            $this->markTestSkipped('Set GRAPHIFY_PYTHON to an environment with Graphify 0.9.109.');
        }
        $directory = sys_get_temp_dir().'/sebra-search-test-'.Str::uuid();
        mkdir($directory, 0700);
        $path = $directory.'/graph.json';
        file_put_contents($path, json_encode([
            'directed' => false, 'multigraph' => false, 'graph' => (object) [],
            'nodes' => [
                ['id' => 'user', 'label' => 'User', 'community' => 0, 'community_name' => 'Accounts', 'source_file' => 'app/User.php'],
                ['id' => 'team', 'label' => 'Team', 'community' => 1, 'community_name' => 'Teams', 'source_file' => 'app/Team.php'],
            ],
            'links' => [['source' => 'user', 'target' => 'team', 'relation' => 'belongs to']],
        ]));
        $communities = [];

        try {
            [$exit, $context, $selection] = app(GraphifySearch::class)->search(
                new GraphQuery('navigate', ['User', 'Team'], ['User', 'Team'], []),
                $path,
                function (array $batch) use (&$communities): void {
                    $communities = [...$communities, ...$batch];
                },
            );
            $this->assertSame(0, $exit);
            $this->assertEqualsCanonicalizing(['user', 'team'], $selection['node_ids']);
            $this->assertNotEmpty($selection['edges']);
            $this->assertStringContainsString('NODE User', $context);
            $this->assertStringContainsString('NODE Team', $context);
            $this->assertStringContainsString('app/User.php', $context);
            $this->assertStringContainsString('--belongs to', $context);
            $this->assertEqualsCanonicalizing(['Accounts', 'Teams'], array_column($communities, 'name'));
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
