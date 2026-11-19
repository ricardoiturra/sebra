<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\GraphifySearch;
use App\Support\GraphQueryInterpreter;
use App\Support\GraphSelection;
use App\Support\ProjectGraph;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class GraphSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selection_contains_only_scoped_nodes_and_real_relations(): void
    {
        $graph = $this->graph();
        $selection = app(GraphSelection::class)->build($graph, [
            'node_ids' => ['user', 'team', 'project', 'FOREIGN'], 'seed_ids' => ['user', 'FOREIGN'],
            'edges' => [['user', 'team'], ['team', 'project'], ['user', 'project'], ['user', 'FOREIGN']],
        ]);
        $this->assertSame(['user', 'team', 'project'], $selection['node_ids']);
        $this->assertSame(['user'], $selection['seed_ids']);
        $this->assertSame(['belongs to', 'owns'], array_column($selection['relations'], 'relation'));
        $this->assertSame(app(GraphSelection::class)->relations($graph), $selection['relations']);
    }

    public function test_path_navigation_uses_a_real_path_and_focus_uses_only_seeds(): void
    {
        $traversal = ['node_ids' => ['user', 'team', 'project'], 'seed_ids' => ['user', 'project'], 'edges' => [['user', 'team'], ['team', 'project']]];
        $selection = app(GraphSelection::class)->build($this->graph(), $traversal, 'path');
        $this->assertSame(['user', 'team', 'project'], $selection['node_ids']);
        $this->assertCount(2, $selection['relations']);
        $focus = app(GraphSelection::class)->build($this->graph(), $traversal, 'focus');
        $this->assertSame(['user', 'project'], $focus['node_ids']);
        $this->assertSame([], $focus['relations']);
    }

    public function test_missing_paths_are_explained_without_inventing_edges(): void
    {
        $selection = app(GraphSelection::class)->build($this->graph(), ['node_ids' => ['user', 'project'], 'seed_ids' => ['user', 'project'], 'edges' => []], 'path');
        $this->assertSame([], $selection['relations']);
        $this->assertStringContainsString('No se encontró un camino', $selection['message']);
    }

    public function test_dependencies_require_graph_direction_and_do_not_invent_dependency_types(): void
    {
        $traversal = ['node_ids' => ['user', 'team', 'project'], 'seed_ids' => ['team'], 'edges' => [['user', 'team'], ['team', 'project']]];
        $directed = app(GraphSelection::class)->build($this->graph() + ['directed' => true], $traversal, 'dependencies');
        $this->assertSame(['team', 'user'], $directed['node_ids']);
        $this->assertSame(['belongs to'], array_column($directed['relations'], 'relation'));
        $undirected = app(GraphSelection::class)->build($this->graph(), $traversal, 'dependencies');
        $this->assertStringContainsString('sin afirmar dependencias', $undirected['message']);
    }

    public function test_grouped_document_nodes_have_an_explicit_display_projection(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $project = $owner->personalTeam()->projects()->create(['name' => 'Docs', 'objective' => 'Docs']);
        $note = $project->notes()->create(['title' => 'Alertas', 'markdown' => 'Content', 'source' => 'newrelic:src/content/docs/alerts/test.md']);
        $graph = ProjectGraph::forProject($project);
        $display = ProjectGraph::forProject($project, forDisplay: true);
        $selection = app(GraphSelection::class)->build($graph, ['node_ids' => ['sebra-note:'.$note->id], 'seed_ids' => ['sebra-note:'.$note->id], 'edges' => []]);
        $this->assertSame(array_column($display['nodes'], 'id'), $selection['display_node_ids']);
        $this->assertSame(['sebra-newrelic:alerts'], $selection['focus_ids']);
    }

    public function test_http_contract_shares_text_speech_and_visual_results_without_reading_llm_text_for_ids(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $project = $owner->personalTeam()->projects()->create(['name' => 'Graph', 'objective' => 'Graph', 'graph' => $this->graph()]);
        config(['services.inference.provider' => 'deepseek', 'services.inference.key' => 'test-key']);
        Http::preventStrayRequests();
        Http::fake(['api.deepseek.com/*' => Http::response(['choices' => [['message' => ['content' => 'User pertenece a Team. No seleccionar INVENTED.']]]])]);
        Redis::shouldReceive('connection->client->executeRaw')->andReturn('1-0');
        $this->mock(GraphifySearch::class)->shouldReceive('search')->once()->andReturn([
            0, 'NODE User [src=app/User.php loc=L1]',
            ['node_ids' => ['user', 'team', 'FOREIGN'], 'seed_ids' => ['user'], 'edges' => [['user', 'team']]],
        ]);
        $response = $this->actingAs($owner)->postJson(route('projects.ask', $project), ['question' => 'User']);
        $response->assertOk()->assertJsonPath('selection.node_ids', ['user', 'team'])
            ->assertJsonPath('selection.status', 'matched')->assertJsonPath('selection.relations.0.source', 'user')
            ->assertJsonPath('selection.relations.0.target', 'team')
            ->assertJsonPath('answer', 'User pertenece a Team. No seleccionar INVENTED.')
            ->assertJsonPath('speech_text', 'User pertenece a Team. No seleccionar INVENTED.');
        Http::assertSentCount(1);
        $this->actingAs(User::factory()->withPersonalTeam()->create())->postJson(route('projects.ask', $project), ['question' => 'User'])->assertForbidden();
    }

    public function test_navigation_modes_are_resolved_by_the_existing_interpreter(): void
    {
        $interpreter = app(GraphQueryInterpreter::class);
        $this->assertSame('dependencies', $interpreter->interpret('¿Qué depende de User?', $this->graph())->navigation);
        $this->assertSame('path', $interpreter->interpret('Muéstrame la relación entre Team y Project', $this->graph())->navigation);
        $this->assertSame('focus', $interpreter->interpret('Enfócate en User', $this->graph())->navigation);
    }

    private function graph(): array
    {
        return ['nodes' => [
            ['id' => 'user', 'label' => 'User'], ['id' => 'team', 'label' => 'Team'], ['id' => 'project', 'label' => 'Project'],
        ], 'links' => [
            ['source' => 'user', 'target' => 'team', 'relation' => 'belongs to'],
            ['source' => 'team', 'target' => 'project', 'relation' => 'owns'],
        ]];
    }
}
